# Flash Sale API

A high-concurrency API for handling flash sales, built with Laravel.

## 🚀 Features

*   **Concurrency Control**: Prevents overselling using database transactions and pessimistic locking (`lockForUpdate`).
*   **High Performance**: Caches product stock availability to reduce database load.
*   **Idempotent Webhooks**: Handles duplicate payment notifications safely.
*   **Background Cleanup**: Automatically releases expired holds.

## 🛠️ Setup Instructions

### Prerequisites
*   PHP 8.2+
*   Composer
*   MySQL

### Installation

1.  **Clone the repository**
    ```bash
    git clone https://github.com/Ahmed-Hany-Aou/flash-sale-api.git
    cd flash-sale-api
    ```

2.  **Install dependencies**
    ```bash
    composer install
    ```

3.  **Configure Environment**
    ```bash
    cp .env.example .env
    # Edit .env and set your DB_DATABASE, DB_USERNAME, DB_PASSWORD
    ```

4.  **Generate Key & Migrate**
    ```bash
    php artisan key:generate
    php artisan migrate --seed
    ```

5.  **Run the Server**
    ```bash
    php artisan serve
    ```

## 📖 API Documentation

### 1. Get Product Details
**GET** `/api/products/{id}`

Returns product details and **available stock** (Total Stock - Active Holds).

**Response:**
```json
{
    "id": 1,
    "name": "Flash Sale Item",
    "total_stock": 100,
    "available_stock": 95,
    "price": "50.00"
}
```

### 2. Place a Hold
**POST** `/api/holds`

Reserves stock for a limited time (15 minutes).

**Body:**
```json
{
    "product_id": 1,
    "quantity": 1
}
```

**Response (201 Created):**
```json
{
    "id": 123,
    "product_id": 1,
    "quantity": 1,
    "expires_at": "2023-10-27T10:15:00.000000Z"
}
```

### 3. Create Order
**POST** `/api/orders`

Converts a valid hold into a final order.

**Body:**
```json
{
    "hold_id": 123
}
```

### 4. Payment Webhook
**POST** `/api/payments/webhook`

Handles payment notifications. Idempotent.

**Body:**
```json
{
    "idempotency_key": "unique_txn_id_123",
    "payload": { "status": "paid" }
}
```

## 🧪 Testing

### Automated Tests
Run the PHPUnit test suite:
```bash
php artisan test
```

### Concurrency Stress Test
Run the Python script to simulate 105 concurrent requests:
```bash
python concurrency_test.py
```
*Expected Result: 100 successes, 5 failures (Insufficient stock).*

## 🏗️ Architecture & Assumptions

1.  **Concurrency**: We use `lockForUpdate()` on the `products` table when creating a hold. This ensures that no two requests can read/update the stock simultaneously, preventing race conditions.
2.  **Caching**: `available_stock` is cached for 60 seconds. The cache is invalidated immediately upon any stock change (Hold created, Order created, Hold released) to ensure data consistency.
3.  **Hold Expiry**: Holds expire after 15 minutes. A scheduled job (`ReleaseExpiredHolds`) runs every minute to clean up expired holds, but the `available_stock` calculation also filters out expired holds in real-time, so the API never returns stale availability.
4.  **Webhooks**: We assume webhooks might arrive out of order or multiple times. We store them in a `processed_webhooks` table with a unique `idempotency_key` to prevent double processing.
