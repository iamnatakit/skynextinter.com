$ErrorActionPreference = 'Stop'
$siteUrl = 'http://localhost:88/skynextinter.com/public/'
try {
    $handler = [System.Net.Http.HttpClientHandler]::new()
    $handler.UseProxy = $false
    $client = [System.Net.Http.HttpClient]::new($handler)
    $client.Timeout = [TimeSpan]::FromSeconds(10)
    $response = $client.GetAsync($siteUrl).GetAwaiter().GetResult()
    if ([int]$response.StatusCode -ne 200) { throw "HTTP $([int]$response.StatusCode)" }
} catch {
    throw "SkyNext is unavailable at $siteUrl . Start Apache in XAMPP (port 88), then run this script again. $($_.Exception.Message)"
} finally {
    if ($response) { $response.Dispose() }
    if ($client) { $client.Dispose() }
    if ($handler) { $handler.Dispose() }
}
Write-Host "SkyNext is running through XAMPP Apache: $siteUrl"
Write-Host "Admin: ${siteUrl}admin"
