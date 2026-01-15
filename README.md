# Booking Conflict Checker

A web application for managing bookings and automatically detecting scheduling conflicts. Built with Laravel (backend) and Vue.js (frontend).

## Tech Stack

### Backend
- **Laravel 12** - PHP Framework
- **MySQL** - Database
- **Laravel Sanctum** - API Authentication
- **Laravel Reverb** - WebSocket Server

### Frontend
- **Vue 3** - JavaScript Framework
- **Vite** - Build Tool
- **Vue Router** - Navigation
- **Pinia** - State Management
- **Axios** - HTTP Client
- **Laravel Echo** - WebSocket Client

## Prerequisites
- **PHP** >= 8.2
- **Composer** - PHP dependency manager
- **Node.js** >= 18.x and npm
- **MySQL** >= 8.0
- **Git**

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd booking-conflict-checker
```

### 2. Backend Setup (Laravel)

#### Navigate to backend directory
```bash
cd backend
```

#### Install PHP dependencies
```bash
composer install
```

#### Create environment file
```bash
cp .env.example .env
```

#### Configure your `.env` file
```env
APP_NAME="Booking Conflict Checker"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=booking_conflict_checker
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_CONNECTION=reverb

REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

#### Create database
```bash
# Using MySQL CLI or phpMyAdmin, create the database:
CREATE DATABASE booking_conflict_checker;
```

#### Generate application key
```bash
php artisan key:generate
```

#### Run migrations
```bash
php artisan migrate
```

#### (Optional) Seed the database with sample data
```bash
php artisan db:seed
```

This will create:
- Admin user: `admin@example.com` / `password`
- Regular user: `user@example.com` / `password`

### 3. Frontend Setup (Vue.js)

#### Navigate to frontend directory
```bash
cd ../frontend
```

#### Install Node dependencies
```bash
npm install
```

#### Configure API endpoint (if needed)
Edit `frontend/src/composables/api.js`:
```javascript
const api = axios.create({
  baseURL: 'http://127.0.0.1:8000/api', // Change if your backend runs on a different port
  // ...
})
```

## Running the Application

### Terminal 1: Laravel Backend Server
```bash
cd backend
php artisan serve
```
Backend will run on: `http://127.0.0.1:8000`

### Terminal 2: Laravel Reverb WebSocket Server (for real-time updates)
```bash
cd backend
php artisan reverb:start
```
WebSocket server will run on: `ws://127.0.0.1:8080`

### Terminal 3: Vue.js Frontend Server
```bash
cd frontend
npm run dev
```
Frontend will run on: `http://localhost:5173`

## Default Credentials

**Admin Account:**
- Email: `admin@example.com`
- Password: `password`

**Regular User Account:**
- Email: `user@example.com`
- Password: `password`

## API Endpoints

### Authentication
- `POST /api/register` - Register new user
- `POST /api/login` - Login user
- `POST /api/logout` - Logout user
- `GET /api/user` - Get authenticated user

### Bookings
- `GET /api/bookings` - Get all bookings (filtered by user role)
- `POST /api/bookings` - Create new booking
- `DELETE /api/bookings/{id}` - Delete booking

### Admin Only
- `GET /api/admin/report` - Get conflict report and statistics

### Running Tests
```bash
# Backend tests
cd backend
php artisan test
```

## Thought Process
- After reviewing the requirements, I first focused on thoroughly understanding the instructions and identifying the primary objective of the system, which is to detect conflicts within bookings.
- I then designed the backend structure, as Laravel is the framework I am most familiar with. During this phase, I set up the necessary dependencies, including Reverb for real-time updates, and accounted for role-based access by separating the admin and normal user views, as they have different permissions and use cases.
- For the frontend, I acknowledged the learning curve since it has been some time since I last worked on a Vue.js-based project. Despite this, I prioritized implementing an efficient and user-friendly booking filtering feature, particularly for the admin interface. The goal was to ensure that admins could easily manage and filter bookings, while maintaining a simple and intuitive experience for normal users as well.

## Coding Process
- To be transparent, I used AI assistance to help me to complete this exam. However, I made sure to fully understand the code and the overall process. I also used AI assistance to generate comments for the code. Based on my experience, especially in projects handled by multiple developers, having clear comments is important. Comments help the next developer understand the purpose of each function and what it is intended to do, making the code easier to maintain and extend.
- I started by implementing the authentication process. Since UI design is not my strong suit, I used AI assistance to generate the UI. I then installed Laravel Sanctum to handle token-based authentication.
- Once login and registration were functional, I focused on the normal user features first. I worked on their dashboard, where users can view all their bookings. I decided to use a DataTable to display the data because, in my opinion, it presents the information more cleanly and efficiently. After setting up the dashboard, I implemented the functionality to add, update, and delete bookings.
- Next, I worked on the admin side, which includes the admin dashboard with statistics, the booking list, and the list of all users.
- For improvements, I added dashboard statistics so that the admin can quickly see the total number of bookings and how many conflicts exist. Additionally, in the booking list, if a conflicting booking is clicked, a pop-up modal appears showing the details of the conflicting schedule.

## Implemented Features
- Authentication
    - User registration and login
    - Authentication state managed with Pinia
    - Route protection based on authentication and role
    - Admin-only access for admin routes
- Booking Management
    - Create, edit, and delete bookings
    - Automatic calculation of booking duration
    - Booking list with sorting and filtering
    - Search bookings by date
- Conflict Detection
    - Detection of overlapping bookings
    - Identification of conflicting bookings (same date and overlapping time)
    - Detection of gaps between bookings
    - Structured conflict report returned via API
- Admin Features
    - Admin-only routes protected by middleware
    - Admin can view all users’ bookings
    - Tabs for:
        - All Bookings
        - Conflicts
    - Visual status indicators (Completed, Conflict)
- Scheduled Task
    - Daily scheduled job that deletes bookings older than 30 days
    - Implemented using Laravel’s scheduler
    - Note: This scheduler needs to be configured on the server to run automatically. You need to add a cron entry on your server that executes Laravel’s scheduler
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Added Improvement
- Admin Conflict Details View
    - To improve real-world usability for admins, this enhancement was added to provide detailed conflict information.
- When a booking is marked as CONFLICT, the admin can view:
    - Which booking(s) it conflicts with
    - The overlapping time range
    - The duration of the overlap
- This allows admins to quickly understand and analyze conflicts without manually comparing booking times.