using System.Net.Http.Headers;
using System.Net.Http.Json;
using System.Text.Json;
using Microsoft.Extensions.Hosting;

sealed class AgentConfiguration
{
    public string? server_url { get; set; }
    public string? token { get; set; }
}

static class AgentConfigurationStore
{
    private static readonly string DirectoryPath = Path.Combine(
        Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
        "Softmor",
        "PrintAgent"
    );

    private static readonly string FilePath = Path.Combine(DirectoryPath, "agent.json");

    public static AgentConfiguration? Load()
    {
        if (!File.Exists(FilePath))
        {
            return null;
        }

        try
        {
            return JsonSerializer.Deserialize<AgentConfiguration>(File.ReadAllText(FilePath));
        }
        catch
        {
            return null;
        }
    }

    public static void Save(AgentConfiguration config)
    {
        Directory.CreateDirectory(DirectoryPath);
        File.WriteAllText(FilePath, JsonSerializer.Serialize(config));
    }
}

sealed class PrintJobPoller(IHttpClientFactory httpClientFactory) : BackgroundService
{
    private readonly SemaphoreSlim configurationChanged = new(0, 1);
    private readonly object sessionLock = new();
    private CancellationTokenSource? activeSession;

    public void ConfigurationChanged()
    {
        lock (sessionLock)
        {
            activeSession?.Cancel();
        }

        if (configurationChanged.CurrentCount == 0)
        {
            configurationChanged.Release();
        }
    }

    protected override async Task ExecuteAsync(CancellationToken stoppingToken)
    {
        var reconnectDelay = TimeSpan.FromSeconds(1);

        while (!stoppingToken.IsCancellationRequested)
        {
            var config = AgentConfigurationStore.Load();
            if (config is null || string.IsNullOrWhiteSpace(config.server_url) || string.IsNullOrWhiteSpace(config.token))
            {
                await configurationChanged.WaitAsync(stoppingToken);
                continue;
            }

            using var session = CancellationTokenSource.CreateLinkedTokenSource(stoppingToken);
            lock (sessionLock)
            {
                activeSession = session;
            }

            try
            {
                await PollAsync(config, session.Token);
                reconnectDelay = TimeSpan.FromSeconds(1);
            }
            catch (OperationCanceledException) when (!stoppingToken.IsCancellationRequested)
            {
                reconnectDelay = TimeSpan.FromSeconds(1);
            }
            catch
            {
                await Task.Delay(reconnectDelay, stoppingToken);
                reconnectDelay = TimeSpan.FromSeconds(Math.Min(reconnectDelay.TotalSeconds * 2, 60));
            }
            finally
            {
                lock (sessionLock)
                {
                    if (activeSession == session)
                    {
                        activeSession = null;
                    }
                }

                configurationChanged.Wait(0);
            }
        }
    }

    private async Task PollAsync(AgentConfiguration config, CancellationToken cancellationToken)
    {
        var client = CreateClient(config);
        var response = await client.GetAsync($"{config.server_url!.TrimEnd('/')}/config", cancellationToken);
        response.EnsureSuccessStatusCode();

        var settings = await response.Content.ReadFromJsonAsync<PollingConfiguration>(cancellationToken: cancellationToken)
            ?? new PollingConfiguration();
        var pollInterval = TimeSpan.FromMilliseconds(Math.Clamp(settings.poll_interval_ms, 1000, 60000));
        var recoveryInterval = TimeSpan.FromSeconds(Math.Clamp(settings.recovery_interval_seconds, 15, 3600));
        var nextRecovery = DateTime.UtcNow;
        var draining = false;

        while (!cancellationToken.IsCancellationRequested)
        {
            var recoveryDue = DateTime.UtcNow >= nextRecovery;
            var processedJob = await ProcessNextJobAsync(config, draining || recoveryDue, cancellationToken);

            if (recoveryDue)
            {
                nextRecovery = DateTime.UtcNow.Add(recoveryInterval);
            }

            if (processedJob)
            {
                draining = true;
                continue;
            }

            draining = false;
            await Task.Delay(pollInterval, cancellationToken);
        }
    }

    private async Task<bool> ProcessNextJobAsync(
        AgentConfiguration config,
        bool force,
        CancellationToken cancellationToken)
    {
        var client = CreateClient(config);

        PrintJobEnvelope? job = null;
        try
        {
            var suffix = force ? "?force=1" : string.Empty;
            var response = await client.GetAsync($"{config.server_url!.TrimEnd('/')}/jobs/next{suffix}", cancellationToken);
            if (response.StatusCode == System.Net.HttpStatusCode.NoContent)
            {
                return false;
            }

            response.EnsureSuccessStatusCode();

            job = await response.Content.ReadFromJsonAsync<PrintJobEnvelope>(cancellationToken: cancellationToken);
            if (job?.payload is null)
            {
                return false;
            }

            var printerName = string.IsNullOrWhiteSpace(job.payload.printer_name) ? "POS-80" : job.payload.printer_name.Trim();
            RawPrinter.SendStringToPrinter(printerName, TicketFormatter.Build(job.payload));
            await client.PostAsync($"{config.server_url.TrimEnd('/')}/jobs/{job.id}/printed", null, cancellationToken);
            return true;
        }
        catch (OperationCanceledException)
        {
            throw;
        }
        catch (Exception ex) when (job is not null)
        {
            try
            {
                await client.PostAsJsonAsync(
                    $"{config.server_url!.TrimEnd('/')}/jobs/{job.id}/failed",
                    new { error = ex.Message },
                    cancellationToken
                );
            }
            catch
            {
            }
            return false;
        }
    }

    private HttpClient CreateClient(AgentConfiguration config)
    {
        var client = httpClientFactory.CreateClient();
        client.Timeout = TimeSpan.FromSeconds(15);
        client.DefaultRequestHeaders.Authorization = new AuthenticationHeaderValue("Bearer", config.token);
        return client;
    }
}

sealed class PollingConfiguration
{
    public int poll_interval_ms { get; set; } = 3000;
    public int recovery_interval_seconds { get; set; } = 60;
}

sealed class PrintJobEnvelope
{
    public long id { get; set; }
    public PrintPayload? payload { get; set; }
}
