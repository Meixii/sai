// Dashboard JavaScript

// Check authentication status
function checkAuth() {
    const token = localStorage.getItem('auth_token');
    if (!token) {
        window.location.href = 'login.html';
        return null;
    }
    return token;
}

// Load user profile
function loadUserProfile() {
    const user = JSON.parse(localStorage.getItem('user'));
    if (user) {
        document.getElementById('userName').textContent = user.name || user.given_name || 'User';
        document.getElementById('userEmail').textContent = user.email || '';
        
        // Set profile image
        const profileImage = document.getElementById('userProfileImage');
        if (user.picture || user.avatar) {
            profileImage.src = user.picture || user.avatar;
        } else {
            profileImage.src = './assets/images/default-profile.png';
        }
        profileImage.onerror = function() {
            this.src = './assets/images/default-profile.png';
        };
    }
}

// Fetch with authentication and error handling
async function fetchWithAuth(url, options = {}) {
    const token = checkAuth();
    if (!token) return null;

    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                ...options.headers,
                'Authorization': `Bearer ${token}`
            }
        });

        if (!response.ok) {
            if (response.status === 401) {
                // Token expired or invalid
                localStorage.removeItem('auth_token');
                window.location.href = 'login.html';
                return null;
            }
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        return null;
    }
}

// Load device information
async function loadDeviceInfo() {
    const data = await fetchWithAuth('/backend/api/device-info.php');
    if (!data) {
        document.getElementById('deviceId').textContent = 'Error loading device info';
        document.getElementById('lastSync').textContent = 'N/A';
        return;
    }

    if (data.devices && data.devices.length > 0) {
        const device = data.devices[0];
        document.getElementById('deviceId').textContent = device.device_id;
        document.getElementById('lastSync').textContent = device.last_sync ? 
            new Date(device.last_sync).toLocaleString() : 'Never';
    } else {
        document.getElementById('deviceId').textContent = 'No device registered';
        document.getElementById('lastSync').textContent = 'N/A';
    }
}

// Load weather information
async function loadWeather() {
    const data = await fetchWithAuth('/backend/api/weather.php');
    if (!data) {
        document.getElementById('currentWeather').innerHTML = 
            '<div class="alert alert-danger">Error loading weather data</div>';
        return;
    }

    const weatherHtml = `
        <div class="temperature">${data.current.temp_c}°C</div>
        <div class="condition">
            <img src="${data.current.condition.icon}" alt="${data.current.condition.text}">
            <div>${data.current.condition.text}</div>
        </div>
        <div class="details mt-3">
            <div>Humidity: ${data.current.humidity}%</div>
            <div>Wind: ${data.current.wind_kph} km/h</div>
        </div>
    `;
    document.getElementById('currentWeather').innerHTML = weatherHtml;
}

// Load next alarm
async function loadNextAlarm() {
    const data = await fetchWithAuth('/backend/api/next-alarm.php');
    if (!data) {
        document.getElementById('nextAlarm').innerHTML = 
            '<div class="alert alert-danger">Error loading alarm data</div>';
        return;
    }

    if (data.has_next_alarm) {
        const alarmTime = new Date(data.next_alarm.time);
        const alarmHtml = `
            <div class="time">${alarmTime.toLocaleTimeString()}</div>
            <div class="date">${alarmTime.toLocaleDateString()}</div>
            <div class="label mt-2">${data.next_alarm.alarm_label || 'Alarm'}</div>
            <div class="device">Device: ${data.next_alarm.device_name}</div>
        `;
        document.getElementById('nextAlarm').innerHTML = alarmHtml;
    } else {
        document.getElementById('nextAlarm').innerHTML = 
            '<div class="no-alarm">No alarms set</div>';
    }
}

// Update current time
function updateCurrentTime() {
    const timeElement = document.getElementById('currentTime');
    timeElement.textContent = new Date().toLocaleString();
}

// Load overview section
async function loadOverview() {
    const data = await fetchWithAuth('/backend/api/sections/overview.php');
    if (!data) {
        document.getElementById('mainContent').innerHTML = 
            '<div class="alert alert-danger">Error loading overview data</div>';
        return;
    }

    const overviewHtml = `
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Devices</h5>
                        <p class="card-text">Total: ${data.devices.total}</p>
                        <p class="card-text">Active: ${data.devices.active}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Alarms</h5>
                        <p class="card-text">Total: ${data.alarms.total}</p>
                        <p class="card-text">Active: ${data.alarms.active}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Weather</h5>
                        <p class="card-text">Temperature: ${data.weather.temperature}°C</p>
                        <p class="card-text">Condition: ${data.weather.condition}</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.getElementById('mainContent').innerHTML = overviewHtml;
}

// Navigation handler
async function handleNavigation() {
    const hash = window.location.hash || '#overview';
    const contentDiv = document.getElementById('mainContent');
    
    // Update page title
    document.getElementById('pageTitle').textContent = 
        hash.substring(1).charAt(0).toUpperCase() + hash.substring(2);
    
    // Remove active class from all nav links
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });
    
    // Add active class to current nav link
    document.querySelector(`a[href="${hash}"]`)?.classList.add('active');
    
    // Show loading state
    contentDiv.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>';
    
    try {
        // Load content based on hash
        switch(hash) {
            case '#overview':
                await loadOverviewSection();
                break;
            case '#alarms':
                await loadAlarmsSection();
                break;
            case '#themes':
                await loadThemesSection();
                break;
            case '#calendar':
                await loadCalendarSection();
                break;
            case '#settings':
                await loadSettingsSection();
                break;
            default:
                contentDiv.innerHTML = '<div class="alert alert-danger">Page not found</div>';
        }
    } catch (error) {
        console.error('Error loading section:', error);
        contentDiv.innerHTML = '<div class="alert alert-danger">Error loading content</div>';
    }
}

// Section loading functions
async function loadOverviewSection() {
    const mainContent = document.getElementById('mainContent');
    await loadOverviewStats();
    await loadRecentActivity();
    await loadUpcomingEvents();
}

async function loadAlarmsSection() {
    const mainContent = document.getElementById('mainContent');
    const data = await fetchWithAuth('/backend/api/alarm/manage.php');
    if (!data) {
        mainContent.innerHTML = '<div class="alert alert-warning">No alarms found</div>';
        return;
    }
    
    // Render alarms section
    mainContent.innerHTML = `
        <div class="row mb-4">
            <div class="col">
                <button class="btn btn-primary" onclick="createNewAlarm()">
                    <i class="fas fa-plus"></i> New Alarm
                </button>
            </div>
        </div>
        <div class="row" id="alarmsList">
            ${renderAlarmsList(data.alarms)}
        </div>
    `;
}

async function loadThemesSection() {
    const mainContent = document.getElementById('mainContent');
    const data = await fetchWithAuth('/backend/api/theme/manage.php');
    if (!data) {
        mainContent.innerHTML = '<div class="alert alert-warning">No themes available</div>';
        return;
    }
    
    // Render themes section
    mainContent.innerHTML = `
        <div class="row" id="themesList">
            ${renderThemesList(data.themes)}
        </div>
    `;
}

async function loadCalendarSection() {
    const mainContent = document.getElementById('mainContent');
    const data = await fetchWithAuth('/backend/api/calendar/events.php');
    if (!data) {
        mainContent.innerHTML = '<div class="alert alert-warning">Calendar not configured</div>';
        return;
    }
    
    // Render calendar section
    mainContent.innerHTML = `
        <div class="row">
            <div class="col">
                <div id="calendar"></div>
            </div>
        </div>
    `;
    
    // Initialize calendar (you'll need to implement this)
    initializeCalendar(data.events);
}

async function loadSettingsSection() {
    const mainContent = document.getElementById('mainContent');
    const data = await fetchWithAuth('/backend/api/user/profile.php');
    if (!data) {
        mainContent.innerHTML = '<div class="alert alert-danger">Error loading settings</div>';
        return;
    }
    
    // Render settings section
    mainContent.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Profile Settings</h5>
                    </div>
                    <div class="card-body">
                        <form id="profileForm">
                            <div class="mb-3">
                                <label>Name</label>
                                <input type="text" class="form-control" id="profileName" value="${data.user.name || ''}">
                            </div>
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" class="form-control" id="profileEmail" value="${data.user.email || ''}" readonly>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Device Settings</h5>
                    </div>
                    <div class="card-body">
                        <!-- Add device settings form here -->
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Set up form handlers
    setupSettingsFormHandlers();
}

// Helper functions for rendering sections
function renderAlarmsList(alarms) {
    return alarms.map(alarm => `
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">${alarm.time}</h5>
                    <p class="card-text">${alarm.label || 'Alarm'}</p>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" 
                            ${alarm.enabled ? 'checked' : ''} 
                            onchange="toggleAlarm(${alarm.id}, this.checked)">
                        <label class="form-check-label">Enabled</label>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

function renderThemesList(themes) {
    return themes.map(theme => `
        <div class="col-md-4 mb-4">
            <div class="card theme-card">
                <div class="card-body">
                    <h5 class="card-title">${theme.name}</h5>
                    <div class="theme-preview" style="background-color: ${theme.primary_color}"></div>
                    <button class="btn btn-primary mt-3" onclick="applyTheme(${theme.id})">
                        Apply Theme
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// Logout function
async function logout() {
    try {
        // Call logout endpoint if it exists
        await fetch('/backend/api/auth/logout.php', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            }
        }).catch(() => {});
    } finally {
        // Clear local storage
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user');
        window.location.href = 'login.html';
    }
}

// Load overview stats
async function loadOverviewStats() {
    const data = await fetchWithAuth('/backend/api/sections/overview.php');
    if (!data) {
        document.getElementById('totalDevices').textContent = '-';
        document.getElementById('activeAlarms').textContent = '-';
        document.getElementById('todayEvents').textContent = '-';
        return;
    }

    document.getElementById('totalDevices').textContent = data.devices.total;
    document.getElementById('activeAlarms').textContent = data.alarms.active;
    document.getElementById('todayEvents').textContent = data.events?.today || 0;

    // Update overview cards with animations
    animateValue('totalDevices', 0, data.devices.total, 1000);
    animateValue('activeAlarms', 0, data.alarms.active, 1000);
    animateValue('todayEvents', 0, data.events?.today || 0, 1000);
}

// Load recent activity
async function loadRecentActivity() {
    const data = await fetchWithAuth('/backend/api/activity/recent.php');
    const activityList = document.getElementById('recentActivity');
    
    if (!data || !data.activities) {
        activityList.innerHTML = '<div class="text-muted">No recent activity</div>';
        return;
    }

    const activityHtml = data.activities.map(activity => `
        <div class="activity-item">
            <div class="activity-icon">
                <i class="fas ${getActivityIcon(activity.type)}"></i>
            </div>
            <div class="activity-content">
                <div class="activity-title">${activity.title}</div>
                <div class="activity-time">${formatTimeAgo(activity.timestamp)}</div>
            </div>
        </div>
    `).join('');

    activityList.innerHTML = activityHtml;
}

// Load upcoming events
async function loadUpcomingEvents() {
    const data = await fetchWithAuth('/backend/api/calendar/events.php');
    const eventsList = document.getElementById('upcomingEvents');
    
    if (!data || !data.events) {
        eventsList.innerHTML = '<div class="text-muted">No upcoming events</div>';
        return;
    }

    const eventsHtml = data.events.map(event => `
        <div class="event-item">
            <div class="event-time">${formatEventTime(event.start)}</div>
            <div class="event-content">
                <div class="event-title">${event.title}</div>
                <div class="event-location">${event.location || ''}</div>
            </div>
        </div>
    `).join('');

    eventsList.innerHTML = eventsHtml;
}

// Helper function to animate number values
function animateValue(elementId, start, end, duration) {
    const element = document.getElementById(elementId);
    if (!element) return;

    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        const value = Math.floor(progress * (end - start) + start);
        element.textContent = value;
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}

// Helper function to get activity icon
function getActivityIcon(type) {
    const icons = {
        'alarm': 'fa-bell',
        'device': 'fa-microchip',
        'weather': 'fa-cloud',
        'calendar': 'fa-calendar',
        'settings': 'fa-cog'
    };
    return icons[type] || 'fa-info-circle';
}

// Helper function to format time ago
function formatTimeAgo(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);

    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return `${Math.floor(seconds / 60)} minutes ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)} hours ago`;
    return date.toLocaleDateString();
}

// Helper function to format event time
function formatEventTime(timestamp) {
    const date = new Date(timestamp);
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);

    if (date.toDateString() === today.toDateString()) {
        return `Today ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
    }
    if (date.toDateString() === tomorrow.toDateString()) {
        return `Tomorrow ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
    }
    return date.toLocaleString([], { 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit', 
        minute: '2-digit'
    });
}

// Initialize page
document.addEventListener('DOMContentLoaded', () => {
    // Check authentication
    if (!checkAuth()) return;
    
    // Load user profile
    loadUserProfile();
    
    // Load initial data
    loadDeviceInfo();
    loadWeather();
    loadNextAlarm();
    
    // Set up navigation
    window.addEventListener('hashchange', handleNavigation);
    handleNavigation();
    
    // Update current time
    updateCurrentTime();
    setInterval(updateCurrentTime, 1000);
    
    // Set up periodic updates
    setInterval(loadWeather, 300000); // Update weather every 5 minutes
    setInterval(loadDeviceInfo, 60000); // Update device info every minute
    setInterval(loadNextAlarm, 60000); // Update next alarm every minute
}); 