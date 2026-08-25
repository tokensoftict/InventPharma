# InventoryPrintAgent

A lightweight Windows service that exposes a REST API for raw ESC/POS thermal printing.
It bridges the gap between a web application and a USB thermal printer.

## Architecture

```
Browser (JavaScript)
    ↓ HTTP POST /print (raw ESC/POS bytes)
InventoryPrintAgent (127.0.0.1:9100)
    ↓ Windows winspool.drv (WritePrinter)
USB Thermal Printer
```

## API Endpoints

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/health` | GET | No | Health check, returns version and status |
| `/printers` | GET | Yes | List installed printers |
| `/printer` | GET | Yes | Get currently selected printer |
| `/printer/select` | POST | Yes | Select a printer by name |
| `/print` | POST | Yes | Send raw ESC/POS bytes to printer |
| `/test-print` | POST | Yes | Print a test page |

## Building

### Prerequisites
- Go 1.21 or later

### Build for Windows (from any OS)

```bash
# AMD64 (most Windows PCs)
GOOS=windows GOARCH=amd64 go build -ldflags="-s -w" -o InventoryPrintAgent.exe .

# ARM64 (Windows on ARM)
GOOS=windows GOARCH=arm64 go build -ldflags="-s -w" -o InventoryPrintAgent.exe .
```

### Build for current OS (testing)

```bash
go build -o InventoryPrintAgent .
```

## Installation

### Option 1: Manual
1. Copy `InventoryPrintAgent.exe` and `config.yaml` to `C:\Program Files\InventoryPrintAgent\`
2. Edit `config.yaml` to set your auth token
3. Open Command Prompt as Administrator:
   ```cmd
   sc create InventoryPrintAgent binPath= "C:\Program Files\InventoryPrintAgent\InventoryPrintAgent.exe" start= auto
   sc start InventoryPrintAgent
   ```

### Option 2: NSIS Installer
1. Build the exe as above
2. Install [NSIS](https://nsis.sourceforge.io)
3. Run: `makensis installer/installer.nsi`
4. Execute the generated `InventoryPrintAgent-Setup.exe`

## Configuration

Edit `config.yaml`:

```yaml
host: "127.0.0.1"      # Only accept local connections
port: 9100              # Port number
auth_token: "your-secret-token"  # Must match Laravel's PRINT_AGENT_TOKEN
default_printer: ""     # Auto-select this printer (optional)
allowed_origins:        # CORS origins
  - "*"
```

## Laravel Configuration

Add to your `.env` file:

```env
PRINT_AGENT_TOKEN=your-secret-token
PRINT_AGENT_URL=http://127.0.0.1:9100
ESCPOS_PAPER_WIDTH=80
ESCPOS_ENCODING=UTF-8
```

## Security

- **Localhost only**: The agent binds to `127.0.0.1`, not accessible from the network
- **Auth token**: All endpoints except `/health` require a Bearer token
- **CORS**: Configurable allowed origins
- **No data storage**: The agent does not store any receipt data

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Agent not reachable | Check if service is running: `sc query InventoryPrintAgent` |
| "No printers found" | Ensure the thermal printer driver is installed in Windows |
| Print fails | Check the printer is online and has paper |
| Permission denied | Run the installer as Administrator |
