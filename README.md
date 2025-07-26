## Project Documentation

### Overview

This project is a Laravel-based application designed to synchronize bookings from a Property Management System (PMS) API into the local system. It uses background jobs to handle data syncing efficiently, with rate-limit handling and retry mechanisms.

<hr>

## Development Setup

### Prerequisites

<ul>
    <li>Docker & Docker Compose installed on your machine</li>
    <li>Laravel Sail (comes bundled with Laravel)</li>
</ul>

## Getting started

### 1. Clone the repository

```bash
git clone <repository-url>
cd <repository-folder>
```

### 2. Start Sail environment and install dependencies
  ``` bash
   ./vendor/bin/sail up -d
   ./vendor/bin/sail composer install
```

### 3. Configure environment
  ``` bash
   cp .env.example .env
   ./vendor/bin/sail artisan key:generate
```
Edit .env as needed (database, cache, queue settings).

### 4. Run migrations
  ``` bash
   ./vendor/bin/sail artisan migrate
```

### 5. Start queue worker
  ``` bash
   ./vendor/bin/sail artisan queue:work
```

### 6. Run sync command
To sync bookings from the PMS:
  ``` bash
   ./vendor/bin/sail artisan app:sync-bookings
```

Optionally, filter bookings updated after a date:

```bash
./vendor/bin/sail artisan app:sync-bookings --updated-after="2025-07-01 00:00:00"
```

## Testing
Run the test suite with:

```bash
./vendor/bin/sail test
```

<ul>
    <li>Feature tests for the booking sync command.</li>
    <li>Unit tests for the sync booking job and PMS service.</li>
    <li>Rate limiting and error handling scenarios.</li>
</ul>

## How It Works

<ol>
    <li>The console command app:sync-bookings fetches bookings from the PMS API.</li>
    <li>For each booking ID, it dispatches a SyncBookingJob.</li>
    <li>SyncBookingJob fetches booking details, room info, room types, and guest data via PMSService.</li>
    <li>It handles API rate limits by retrying with exponential backoff.</li>
    <li>Booking and related data are persisted using repository classes within a database transaction.</li>
</ol>
