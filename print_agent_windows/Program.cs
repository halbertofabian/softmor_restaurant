using System.Runtime.InteropServices;
using System.Text;
using System.Text.Json;
using System.Text.Json.Serialization;
using Microsoft.AspNetCore.Builder;
using Microsoft.AspNetCore.Http;
using Microsoft.Extensions.Hosting;

Encoding.RegisterProvider(CodePagesEncodingProvider.Instance);

var builder = WebApplication.CreateBuilder(args);

builder.WebHost.UseUrls("http://localhost:8000");

var app = builder.Build();

app.Use(async (context, next) =>
{
    context.Response.Headers.Append("Access-Control-Allow-Origin", "*");
    context.Response.Headers.Append("Access-Control-Allow-Headers", "Content-Type");
    context.Response.Headers.Append("Access-Control-Allow-Methods", "POST, GET, OPTIONS");

    if (HttpMethods.IsOptions(context.Request.Method))
    {
        context.Response.StatusCode = StatusCodes.Status200OK;
        return;
    }

    await next();
});

app.MapGet("/api/printer/list", () =>
{
    try
    {
        var printers = RawPrinter.ListPrinters();
        return Results.Json(new { status = "success", printers });
    }
    catch (Exception ex)
    {
        return Results.Json(new { status = "error", message = ex.Message }, statusCode: 500);
    }
});

app.MapGet("/printer/list", () =>
{
    try
    {
        var printers = RawPrinter.ListPrinters();
        return Results.Json(new { status = "success", printers });
    }
    catch (Exception ex)
    {
        return Results.Json(new { status = "error", message = ex.Message }, statusCode: 500);
    }
});

app.MapPost("/api/printer/raw", async (HttpContext ctx) =>
{
    try
    {
        var payload = await ctx.Request.ReadFromJsonAsync<PrintPayload>();
        if (payload is null)
        {
            return Results.Json(new { status = "error", message = "Invalid JSON" }, statusCode: 400);
        }

        var printerName = string.IsNullOrWhiteSpace(payload.printer_name) ? "POS-80" : payload.printer_name.Trim();
        var content = TicketFormatter.Build(payload);

        RawPrinter.SendStringToPrinter(printerName, content);

        return Results.Json(new { status = "success", message = "Printed successfully" });
    }
    catch (Exception ex)
    {
        return Results.Json(new { status = "error", message = ex.Message }, statusCode: 500);
    }
});

app.MapPost("/printer/raw", async (HttpContext ctx) =>
{
    try
    {
        var payload = await ctx.Request.ReadFromJsonAsync<PrintPayload>();
        if (payload is null)
        {
            return Results.Json(new { status = "error", message = "Invalid JSON" }, statusCode: 400);
        }

        var printerName = string.IsNullOrWhiteSpace(payload.printer_name) ? "POS-80" : payload.printer_name.Trim();
        var content = TicketFormatter.Build(payload);

        RawPrinter.SendStringToPrinter(printerName, content);

        return Results.Json(new { status = "success", message = "Printed successfully" });
    }
    catch (Exception ex)
    {
        return Results.Json(new { status = "error", message = ex.Message }, statusCode: 500);
    }
});

app.MapGet("/", () => Results.Json(new
{
    service = "Softmor Print Agent",
    status = "running",
    endpoints = new[] { "/api/printer/list", "/api/printer/raw" }
}));

app.Run();

sealed class PrintPayload
{
    public string? type { get; set; }
    public string? printer_name { get; set; }
    public string? header { get; set; }
    public string? pre_check_disclaimer { get; set; }
    public string? branch_name { get; set; }
    public string? date { get; set; }

    [JsonConverter(typeof(FlexibleStringConverter))]
    public string? ticket_id { get; set; }

    [JsonNumberHandling(JsonNumberHandling.AllowReadingFromString)]
    public decimal? total { get; set; }

    public string? table_name { get; set; }
    public string? waiter_name { get; set; }
    public List<PrintItem>? items { get; set; }
    public bool? tips_enabled { get; set; }
    public List<TipSuggestion>? tip_suggestions { get; set; }
}

sealed class PrintItem
{
    public string? name { get; set; }

    [JsonNumberHandling(JsonNumberHandling.AllowReadingFromString)]
    public decimal? quantity { get; set; }

    [JsonNumberHandling(JsonNumberHandling.AllowReadingFromString)]
    public decimal? price { get; set; }

    public string? notes { get; set; }
}

sealed class TipSuggestion
{
    [JsonNumberHandling(JsonNumberHandling.AllowReadingFromString)]
    public decimal? percent { get; set; }

    [JsonNumberHandling(JsonNumberHandling.AllowReadingFromString)]
    public decimal? amount { get; set; }
}

sealed class FlexibleStringConverter : JsonConverter<string?>
{
    public override string? Read(ref Utf8JsonReader reader, Type typeToConvert, JsonSerializerOptions options)
    {
        return reader.TokenType switch
        {
            JsonTokenType.String => reader.GetString(),
            JsonTokenType.Number => reader.TryGetInt64(out var l)
                ? l.ToString()
                : reader.GetDouble().ToString(System.Globalization.CultureInfo.InvariantCulture),
            JsonTokenType.True => "true",
            JsonTokenType.False => "false",
            JsonTokenType.Null => null,
            _ => throw new JsonException($"Unsupported token type for string conversion: {reader.TokenType}")
        };
    }

    public override void Write(Utf8JsonWriter writer, string? value, JsonSerializerOptions options)
    {
        if (value is null)
        {
            writer.WriteNullValue();
            return;
        }

        writer.WriteStringValue(value);
    }
}

static class TicketFormatter
{
    public static string Build(PrintPayload data)
    {
        var sb = new StringBuilder();
        var isKitchen = string.Equals(data.type, "kitchen", StringComparison.OrdinalIgnoreCase);

        if (isKitchen)
        {
            sb.AppendLine("**************");
            sb.AppendLine($" MESA {(data.table_name ?? "?").ToUpperInvariant()}");
            sb.AppendLine("**************");
            sb.AppendLine();

            foreach (var item in data.items ?? Enumerable.Empty<PrintItem>())
            {
                var qty = item.quantity ?? 1;
                var name = item.name ?? "Producto";
                sb.AppendLine($"{qty:0.##} x {name}");

                if (!string.IsNullOrWhiteSpace(item.notes))
                {
                    sb.AppendLine($"   * {item.notes}");
                }

                sb.AppendLine();
            }

            sb.AppendLine($"--- {DateTime.Now:HH:mm} ---");
            sb.AppendLine($"Mesero: {data.waiter_name ?? "N/A"}");
            sb.AppendLine();
            sb.AppendLine();
        }
        else
        {
            sb.AppendLine(data.header ?? "Ticket de Venta");
            sb.AppendLine($"Sucursal: {data.branch_name ?? "Principal"}");
            sb.AppendLine(data.date ?? DateTime.Now.ToString("dd/MM/yyyy hh:mm tt"));
            sb.AppendLine($"Ticket #: {data.ticket_id ?? "N/A"}");
            sb.AppendLine("--------------------------------");

            foreach (var item in data.items ?? Enumerable.Empty<PrintItem>())
            {
                var name = item.name ?? "Producto";
                var qty = item.quantity ?? 1;
                var price = item.price ?? 0;

                if (name.Length > 20)
                {
                    name = name[..20];
                }

                sb.AppendLine($"{qty:0.##} x {name}");
                sb.AppendLine($"${(price * qty):0.00}");
            }

            sb.AppendLine("--------------------------------");
            sb.AppendLine($"TOTAL: ${(data.total ?? 0):0.00}");

            var isPreCheck = string.Equals(data.type, "pre_check", StringComparison.OrdinalIgnoreCase);
            if (isPreCheck && (data.tips_enabled ?? false))
            {
                var tips = data.tip_suggestions ?? new List<TipSuggestion>();
                if (tips.Count > 0)
                {
                    sb.AppendLine("--------------------------------");
                    sb.AppendLine("PROPINA SUGERIDA");
                    foreach (var tip in tips)
                    {
                        var percent = tip.percent ?? 0;
                        var amount = tip.amount ?? 0;
                        sb.AppendLine($"{percent:0.##}%: ${amount:0.00}");
                    }
                }
            }

            if (isPreCheck && !string.IsNullOrWhiteSpace(data.pre_check_disclaimer))
            {
                sb.AppendLine();
                sb.AppendLine(data.pre_check_disclaimer);
            }

            sb.AppendLine();
            sb.AppendLine();
        }

        sb.Append('\u001D');
        sb.Append('V');
        sb.Append((char)66);
        sb.Append((char)0);

        return sb.ToString();
    }
}

static class RawPrinter
{
    private const byte Esc = 0x1B;
    private const byte EscPosCodePage858 = 19;

    [DllImport("winspool.Drv", EntryPoint = "OpenPrinterW", SetLastError = true, CharSet = CharSet.Unicode)]
    private static extern bool OpenPrinter(string pPrinterName, out IntPtr phPrinter, IntPtr pDefault);

    [DllImport("winspool.Drv", EntryPoint = "ClosePrinter", SetLastError = true)]
    private static extern bool ClosePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "StartDocPrinterW", SetLastError = true, CharSet = CharSet.Unicode)]
    private static extern bool StartDocPrinter(IntPtr hPrinter, int level, [In] DOC_INFO_1 di);

    [DllImport("winspool.Drv", EntryPoint = "EndDocPrinter", SetLastError = true)]
    private static extern bool EndDocPrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "StartPagePrinter", SetLastError = true)]
    private static extern bool StartPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "EndPagePrinter", SetLastError = true)]
    private static extern bool EndPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "WritePrinter", SetLastError = true)]
    private static extern bool WritePrinter(IntPtr hPrinter, IntPtr pBytes, int dwCount, out int dwWritten);

    [DllImport("winspool.Drv", EntryPoint = "EnumPrintersW", SetLastError = true, CharSet = CharSet.Unicode)]
    private static extern bool EnumPrinters(int flags, string? name, int level, IntPtr pPrinterEnum, int cbBuf, out int pcbNeeded, out int pcReturned);

    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Unicode)]
    private class DOC_INFO_1
    {
        public string pDocName = "Softmor Ticket";
        public string pOutputFile = "";
        public string pDataType = "RAW";
    }

    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Unicode)]
    private struct PRINTER_INFO_4
    {
        public string pPrinterName;
        public string pServerName;
        public uint Attributes;
    }

    public static void SendStringToPrinter(string printerName, string content)
    {
        var bytes = BuildEscPosBytes(content);
        var unmanagedBytes = Marshal.AllocCoTaskMem(bytes.Length);

        try
        {
            Marshal.Copy(bytes, 0, unmanagedBytes, bytes.Length);
            SendBytesToPrinter(printerName, unmanagedBytes, bytes.Length);
        }
        finally
        {
            Marshal.FreeCoTaskMem(unmanagedBytes);
        }
    }

    private static byte[] BuildEscPosBytes(string content)
    {
        // Use ESC/POS code page 858 (Western Europe) for proper Spanish accents and ñ.
        // Many thermal printers do not render raw UTF-8 correctly.
        var textEncoding = Encoding.GetEncoding(858);
        var textBytes = textEncoding.GetBytes(content);

        var output = new byte[textBytes.Length + 5];
        output[0] = Esc; // ESC @ -> Initialize printer
        output[1] = (byte)'@';
        output[2] = Esc; // ESC t n -> Select character code table
        output[3] = (byte)'t';
        output[4] = EscPosCodePage858;

        Buffer.BlockCopy(textBytes, 0, output, 5, textBytes.Length);
        return output;
    }

    private static void SendBytesToPrinter(string printerName, IntPtr pBytes, int count)
    {
        if (!OpenPrinter(printerName, out var hPrinter, IntPtr.Zero))
        {
            ThrowLastWin32($"No se pudo abrir la impresora '{printerName}'");
        }

        try
        {
            var docInfo = new DOC_INFO_1();

            if (!StartDocPrinter(hPrinter, 1, docInfo))
            {
                ThrowLastWin32("No se pudo iniciar el documento de impresión");
            }

            try
            {
                if (!StartPagePrinter(hPrinter))
                {
                    ThrowLastWin32("No se pudo iniciar la página de impresión");
                }

                try
                {
                    if (!WritePrinter(hPrinter, pBytes, count, out var written) || written != count)
                    {
                        ThrowLastWin32("No se pudo escribir en la cola de impresión");
                    }
                }
                finally
                {
                    EndPagePrinter(hPrinter);
                }
            }
            finally
            {
                EndDocPrinter(hPrinter);
            }
        }
        finally
        {
            ClosePrinter(hPrinter);
        }
    }

    public static List<string> ListPrinters()
    {
        const int PRINTER_ENUM_LOCAL = 0x2;
        const int PRINTER_ENUM_CONNECTIONS = 0x4;

        var flags = PRINTER_ENUM_LOCAL | PRINTER_ENUM_CONNECTIONS;

        EnumPrinters(flags, null, 4, IntPtr.Zero, 0, out var needed, out var returned);
        if (needed <= 0)
        {
            return new List<string>();
        }

        var buffer = Marshal.AllocHGlobal(needed);
        try
        {
            if (!EnumPrinters(flags, null, 4, buffer, needed, out _, out returned))
            {
                ThrowLastWin32("No se pudieron listar las impresoras");
            }

            var result = new List<string>();
            var structSize = Marshal.SizeOf<PRINTER_INFO_4>();

            for (var i = 0; i < returned; i++)
            {
                var current = IntPtr.Add(buffer, i * structSize);
                var info = Marshal.PtrToStructure<PRINTER_INFO_4>(current);
                if (!string.IsNullOrWhiteSpace(info.pPrinterName))
                {
                    result.Add(info.pPrinterName.Trim());
                }
            }

            return result.Distinct(StringComparer.OrdinalIgnoreCase).OrderBy(x => x).ToList();
        }
        finally
        {
            Marshal.FreeHGlobal(buffer);
        }
    }

    private static void ThrowLastWin32(string message)
    {
        var error = Marshal.GetLastWin32Error();
        throw new InvalidOperationException($"{message}. Win32Error={error}");
    }
}
