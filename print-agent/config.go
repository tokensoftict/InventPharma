package main

import (
	"os"

	"gopkg.in/yaml.v3"
)

// Config holds the application configuration.
type Config struct {
	Host           string   `yaml:"host"`
	Port           int      `yaml:"port"`
	AuthToken      string   `yaml:"auth_token"`
	DefaultPrinter string   `yaml:"default_printer"`
	AllowedOrigins []string `yaml:"allowed_origins"`
	ServiceName    string   `yaml:"service_name"`
}

// DefaultConfig returns a config with sensible defaults.
func DefaultConfig() *Config {
	return &Config{
		Host:           "127.0.0.1",
		Port:           9100,
		AuthToken:      "",
		DefaultPrinter: "",
		AllowedOrigins: []string{"*"},
		ServiceName:    "InventoryPrintAgent",
	}
}

// LoadConfig reads a YAML config file.
func LoadConfig(path string) (*Config, error) {
	cfg := DefaultConfig()

	data, err := os.ReadFile(path)
	if err != nil {
		return nil, err
	}

	if err := yaml.Unmarshal(data, cfg); err != nil {
		return nil, err
	}

	// Ensure required defaults
	if cfg.Host == "" {
		cfg.Host = "127.0.0.1"
	}
	if cfg.Port == 0 {
		cfg.Port = 9100
	}
	if cfg.ServiceName == "" {
		cfg.ServiceName = "InventoryPrintAgent"
	}

	return cfg, nil
}
