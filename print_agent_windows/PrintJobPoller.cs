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
    protected override async Task ExecuteAsync(CancellationToken stoppingToken)
    {
        while (!stoppingToken.IsCancellationRequested)
        {
            var processedJob = false;
            var config = AgentConfigurationStore.Load();
            if (config is not null && !string.IsNullOrWhiteSpace(config.server_url) && !string.IsNullOrWhiteSpace(config.token))
            {
                processedJob = await ProcessNextJob(config, stoppingToken);
            }

            if (!processedJob)
            {
                await Task.Delay(TimeSpan.FromSeconds(3), stoppingToken);
            }
        }
    }

    private async Task<bool> ProcessNextJob(AgentConfiguration config, CancellationToken cancellationToken)
    {
        var client = httpClientFactory.CreateClient();
        client.Timeout = TimeSpan.FromSeconds(8);
        client.DefaultRequestHeaders.Authorization = new AuthenticationHeaderValue("Bearer", config.token);

        PrintJobEnvelope? job = null;
        try
        {
            var response = await client.GetAsync($"{config.server_url!.TrimEnd('/')}/jobs/next", cancellationToken);
            if (response.StatusCode == System.Net.HttpStatusCode.NoContent || !response.IsSuccessStatusCode)
            {
                return false;
            }

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
        catch
        {
            return false;
        }
    }
}

sealed class PrintJobEnvelope
{
    public long id { get; set; }
    public PrintPayload? payload { get; set; }
}
