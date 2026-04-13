# Webhook Processing System

A robust, enterprise-grade webhook processing backend built on Laravel 11. 

This application was structured strictly around a layered architecture to ensure scalability, ease of testing, and perfectly decoupled business logic.

## Architecture

The system enforces the following rigid flow for all requests:
`HTTP Controller → Service Layer → Repository Interface → Eloquent Model`

- **No shortcuts:** The controllers and service layer are forbidden from using Eloquent commands directly (e.g., `Payment::create()`). All external database requests go through interfaces.
- **FormRequests:** Validation is extracted out of the Controller entirely. Requests are sanitized via `StorePaymentWebhookRequest`—if validation fails, a `422 Unprocessable Entity` JSON response is thrown without the controller ever knowing the request existed.

## Implementation Phases

### 1. The Data Layer (Migrations & Models)
Created the `event_logs` and `payments` tables. 
- The `payments` table uses a string for the primary key (`payment_id`) as it receives IDs directly from webhook providers.
- The `event_logs` table serves as an immutable timeline of every transaction ever processed, making it the ultimate source of truth.

### 2. The Contract Layer (Repository Interfaces)
We defined `PaymentRepositoryInterface` and `EventLogRepositoryInterface`, then created their Eloquent implementations.
- These are bound securely inside `AppServiceProvider`.
- This ensures our app logic relies on *contracts* rather than hardcoded database engines. If we migrate an aggregate log table to MongoDB later, the rest of the application remains completely untouched.

### 3. The Service Layer (Idempotency)
`WebhookService` handles the core flow logic:
1. Try to save the event to the `event_logs` table immediately.
2. The `event_id` column is set to `unique()`. Thus, if a duplicate webhook hits our system, the database strictly blocks the transaction and throws a `QueryException`.
3. The Service catches this exact duplicate-entry exception, and safely terminates the system process securely before it updates the primary `Payment` states. 
4. If it is *not* a duplicate, the event gets logged safely and the actual `Payment` record is upserted.

### 4. HTTP Layer (Controllers & Routing)
- Endpoint: `POST /webhooks/payment` handles the incoming payloads. Uses `StorePaymentWebhookRequest` to enforce strict formatting requirements (e.g. `currency` must be 3 characters, `amount` strictly numeric and positive).
- CSRF token validation was disabled specifically for `webhooks/*` within `bootstrap/app.php` so standard machine API callers are not halted.
- Controllers are incredibly lean, returning basic JSON payloads and HTTP 200s for success. Duplicate webhooks correctly return a `200 Success` despite being silently blocked by the idempotency layer, so that the external partner network assumes successful delivery and ceases retries.

---

## How to Test

You can test the system manually via `curl`. 

### 1. Test a Valid Webhook
Notice that running this exact command multiple times will continually return `200 Success` (correct webhook behavior), but it will only actually process in your database **once** due to the `event_id` idempotency lock.

```bash
curl -v -X POST http://localhost:8000/webhooks/payment \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d '{
  "event_id": "evt_abc123",
  "payment_id": "pay_xyz987",
  "event": "payment.success",
  "amount": 49.99,
  "currency": "USD",
  "user_id": "user_1001",
  "timestamp": "2026-04-10 12:00:00"
}'
```

### 2. Test the Validation (FormRequest) Shield
Try sending a `currency` that is 4 characters long, or a negative `amount`, or omitting the `event_id`:

```bash
curl -v -X POST http://localhost:8000/webhooks/payment \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d '{
  "payment_id": "pay_xyz987",
  "event": "payment.success",
  "amount": -50.00,
  "currency": "USDS",
  "user_id": "user_1001",
  "timestamp": "2026-04-10 12:00:00"
}'
```
You will immediately receive a strict `422 Unprocessable Entity` JSON response structured with precise error targets.

### 3. Testing Protected Endpoints (Sanctum Auth)
Because the `GET /payments` and `POST /payments/{id}/refund` endpoints are now locked behind Sanctum authentication, you must log in first to generate a Bearer Token.

**Step A: Get your API Token**
Run this to log in as the auto-generated admin and copy the `token` string returned in the JSON:
```bash
curl -X POST http://localhost:8000/api/login \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d '{
  "email": "admin@admin.com",
  "password": "secret"
}'
```

**Step B: Use the Token to Fetch Payments**
Replace `<YOUR_TOKEN>` with the string you just copied:
```bash
curl -X GET http://localhost:8000/api/payments \
-H "Accept: application/json" \
-H "Authorization: Bearer <YOUR_TOKEN>"
```

**Step C: Trigger a Manual Refund**
```bash
curl -X POST http://localhost:8000/api/payments/pay_xyz987/refund \
-H "Accept: application/json" \
-H "Authorization: Bearer <YOUR_TOKEN>"
```
