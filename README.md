# SAI: Smart Alarm IoT

A Raspberry Pi-based Smart Alarm system with web configuration interface and IoT capabilities.

## Project Components

1. RPi3 Application
   - Smart alarm display interface
   - Real-time weather updates
   - Calendar event notifications
   - Configurable RGB lighting
   - Custom alarm sounds

2. Web Configuration Portal
   - Google SSO authentication
   - Theme customization
   - Alarm sound management
   - RGB light configuration
   - Calendar integration
   - Weather settings

3. Backend Server
   - RESTful API endpoints
   - Database management
   - Weather API integration
   - Google Calendar integration

## Setup Requirements

### Hardware
- Raspberry Pi 3 Model B V1.2
- Raspberry Pi Display V1.1
- 1x8 RGB Module WS2812B
- 8 Ohms 0.5W Mylar Type Speakers (2pcs)
- DS3231 w/ AT24C32 EEPROM Real-Time Clock Module

### API Keys & Services
- WeatherAPI Integration
- Google OAuth2.0
- Google Calendar API
- Custom Email Service

## Installation & Setup

Detailed setup instructions will be provided in each component's directory.

## Project Structure
```
├── website/           # Web Configuration Portal
├── backend/           # PHP Backend Server
└── app/              # Raspberry Pi Application
```

## License
All rights reserved.
