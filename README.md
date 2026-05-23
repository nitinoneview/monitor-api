# 🖥️ Server Monitor SaaS

A full-stack server monitoring system built from scratch by a Production Support Engineer.  
Monitors Linux server health in real-time and displays metrics on a live web dashboard.

> 🔗 Live at: [monitor-frontend-flame.vercel.app](https://monitor-frontend-flame.vercel.app)

---

## 🚀 Live Demo

🔗 **Dashboard:** [monitor-frontend-flame.vercel.app](https://monitor-frontend-flame.vercel.app)  
🔗 **API:** [monitor-api-e8ez.onrender.com/api.php](https://monitor-api-e8ez.onrender.com/api.php)

---

## 📊 What It Monitors

| Metric      | Description 			|
|-------------|---------------------------------|
| CPU Usage   | Real-time processor utilization |
| RAM Usage   | Memory consumption percentage   |
| Disk Usage  | Root partition usage            |
| Swap Usage  | Virtual memory usage 		|
| Load Average| 1m / 5m / 15m system load 	|
| Processes   | Total running processes 	|
| Threads     | Total active threads 		|
| IOWait      | Disk I/O wait percentage 	|
| Users       | Logged-in user count 		|
| Uptime      | System uptime 			|

------------------------------------------------------
## 🏗️ Architecture

```
RHEL 8 Server (Agent)
      │
      │  curl POST (JSON) — every 1 min via cron
      ▼
PHP REST API (Render.com)
      │
      │  SQL INSERT
      ▼
NeonDB (PostgreSQL)
      │
      │  GET /api.php
      ▼
Frontend Dashboard (Vercel)

 HTML + CSS + JavaScript
```

-----------------------------------------------------

## 🛠️ Tech Stack ####################

| Layer       | Technology 			  |
|-------------|-----------------------------------|
| Agent       | Bash Shell Script + Cron Job 
| Backend API | PHP 8.1 — REST API 	     
| Database    | NeonDB (PostgreSQL)         
| Frontend    | HTML + CSS + Vanilla JS      
| Deployment  | Render.com (API) + Vercel (Frontend) 
| Uptime      | UptimeRobot (24/7 monitoring)
| OS          | Red Hat Enterprise Linux 8 

-------------------------------------------------------------
## 📁 Project Structure

```
server-monitor/
├── monitor-agent/
│   ├── monitor.sh        # Bash agent — collects metrics
│   └── cleanup.sh        # Auto-deletes records older than 7 days
│
├── monitor-api/
│   ├── api.php           # PHP REST API
│   ├── Dockerfile        # Docker container for Render
│   └── .env.example      # Environment variable template
│
└── monitor-frontend/
    └── index.html        # Dashboard — HTML/CSS/JS
```

--------------------------------------------------------------

## ⚙️ Features ################################

- ✅ Real-time metrics collection via Bash script
- ✅ Automated data collection via Linux Cron Job
- ✅ REST API with API key authentication
- ✅ PostgreSQL database with auto-cleanup (7 days retention)
- ✅ Color-coded gauges (Green / Yellow / Red thresholds)
- ✅ Server Online / Offline detection with banner
- ✅ IST timezone support
- ✅ Fully responsive — Mobile + Tablet + Desktop
- ✅ 24/7 uptime via UptimeRobot keep-alive

----------------------------------------------------------------

## 🚀 Deployment Guide ##

### Step 1 — NeonDB Setup

1. Create free account at [neon.tech](https://neon.tech)
2. Create new project: `server-monitor`
3. Run this SQL in SQL Editor:

```sql
CREATE TABLE servers (
    id SERIAL PRIMARY KEY,
    hostname VARCHAR(100) UNIQUE NOT NULL,
    api_key VARCHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE metrics (
    id SERIAL PRIMARY KEY,
    server_id INT REFERENCES servers(id),
    cpu_percent NUMERIC(5,2),
    ram_percent NUMERIC(5,2),
    disk_percent NUMERIC(5,2),
    uptime VARCHAR(100),
    iowait NUMERIC(5,2) DEFAULT 0,
    swap_percent NUMERIC(5,2) DEFAULT 0,
    load_1 NUMERIC(5,2) DEFAULT 0,
    load_5 NUMERIC(5,2) DEFAULT 0,
    load_15 NUMERIC(5,2) DEFAULT 0,
    processes INT DEFAULT 0,
    threads INT DEFAULT 0,
    users INT DEFAULT 0,
    recorded_at TIMESTAMP DEFAULT NOW()
);

INSERT INTO servers (hostname, api_key)
VALUES ('your-hostname', 'your-api-key-here');
```

---

### Step 2 — API Deploy (Render.com)

1. Fork/clone this repo to your GitHub
2. Go to [render.com](https://render.com) → New Web Service
3. Connect your GitHub repo: `monitor-api`
4. Settings:
   - **Runtime:** Docker
   - **Region:** Singapore
5. Add Environment Variable:
   - Key: `DATABASE_URL`
   - Value: your NeonDB connection string
6. Click **Deploy**

---

### Step 3 — Bash Agent Setup (Linux Server)

```bash
# Clone repo
git clone https://github.com/YOUR_USERNAME/monitor-api.git
cd monitor-api

# Edit agent config
nano monitor-agent/monitor.sh
# Set API_URL to your Render URL
# Set API_KEY to match your database entry

# Make executable
chmod +x monitor-agent/monitor.sh

# Test
bash monitor-agent/monitor.sh

# Add cron job (every 1 minute)
crontab -e
# Add: * * * * * bash /path/to/monitor.sh >> /tmp/monitor.log 2>&1
```

---------------------------------------------------------------------------

### Step 4 — Frontend Deploy (Vercel)

1. Push `monitor-frontend` folder to GitHub
2. Go to [vercel.com](https://vercel.com) → New Project
3. Connect your GitHub repo: `monitor-frontend`
4. Click **Deploy** — no configuration needed
5. Update `API` variable in `index.html` to your Render URL

---

### Step 5 — Keep Alive (UptimeRobot)

1. Go to [uptimerobot.com](https://uptimerobot.com)
2. Add New Monitor:
   - Type: **HTTP(s)**
   - URL: `https://your-api.onrender.com/api.php?ping=1`
   - Interval: **5 minutes**
   - Region: **Asia**
3. Save — Render will never sleep again

---

## 🔐 Environment Variables

Create `.env` file (never commit to GitHub):

# DATABASE_URL=postgresql://user:password@host/dbname?sslmode=require

-----------------------------------------------------------------------

## 📈 API Endpoints

| Method| Endpoint         | Description                |
|-------|------------------|----------------------------|
| `GET` | `/api.php`       | Fetch latest 20 metrics    |
| `GET` | `/api.php?ping=1`| Health check (UptimeRobot) |
| `POST`| `/api.php`       | Submit metrics from agent  |
| `HEAD`| `/api.php`       | Uptime check               |

### POST Request Body ##########################

```json
{
  "hostname": "your-server",
  "api_key": "your-api-key",
  "cpu_percent": 12.5,
  "ram_percent": 55.0,
  "disk_percent": 25.0,
  "swap_percent": 18.0,
  "iowait": 0.41,
  "load_1": 0.14,
  "load_5": 0.36,
  "load_15": 0.28,
  "processes": 332,
  "threads": 803,
  "users": 2,
  "uptime": "6 hours, 44 minutes"
}
```

---

## 👨‍💻 About

Built by **Nitin** — Production Support Engineer

This project demonstrates hands-on skills in:
- Linux system administration & shell scripting
- REST API development & deployment
- PostgreSQL database design
- Cloud deployment (Render, Vercel, NeonDB)
- Real-time monitoring & alerting concepts
- Docker containerization
- Full-stack web development

---------------------------------------------------------

## 📬 Contact

- GitHub: [@nitinoneview](https://github.com/nitinoneview)
- Dashboard: [monitor-frontend-flame.vercel.app](https://monitor-frontend-flame.vercel.app)
