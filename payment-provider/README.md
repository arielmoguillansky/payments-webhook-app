# Payment Provider (Plain PHP)

This project is a minimal payment provider service written in plain PHP. It follows a Hexagonal Architecture (Ports and Adapters) with Domain-Driven Design-style boundaries so business logic stays isolated from HTTP and persistence details.

## What It Does

- Tokenizes card details via `POST /api/tokens`
- Charges a payment token via `POST /api/charges`
- Persists payment state into a JSON file
- Sends a webhook callback with the final payment status

## Architecture Overview

The code is split into 3 logical layers:

- **Domain** (`src/Domain`)
  - Core models and contracts
  - No HTTP, no storage, no framework dependency
- **Application** (`src/Application`)
  - Use cases that orchestrate domain objects and ports
  - Defines the payment flow (token parsing, payment initiation, processing, webhook dispatch)
- **Infrastructure** (`src/Infrastructure`)
  - Adapters for external concerns:
    - HTTP controllers
    - JSON-file persistence
    - Webhook HTTP client

Entrypoint and lightweight composition:

- `public/index.php` acts as:
  - router (`/api/tokens`, `/api/charges`)
  - dependency container wiring use cases and adapters
  - JSON response dispatcher

## Request Flows

### 1) Tokenization Flow

1. `POST /api/tokens` reaches `TokenController`
2. Controller extracts `card_number`, `exp_month`, `exp_year`, `cvc`
3. `TokenizeCardUseCase` validates basic card length
4. `CardToken::create()` generates a random `tok_...` token
5. Controller returns JSON:
   - `{ "status": "success", "data": { "token": "tok_..." } }`

### 2) Charge Flow

1. `POST /api/charges` reaches `ChargeController`
2. Controller extracts `token`, `amount`, `currency`, `user_id`, `webhook_url`
3. `ChargePaymentUseCase`:
   - recreates token via `CardToken::fromString`
   - creates a `Payment` aggregate with `pending` status
   - persists initial state via `PaymentRepositoryInterface`
   - processes payment (`success` ~90%, `failed` ~10%)
   - persists final state
   - emits webhook event via `WebhookSenderInterface`
4. Controller returns JSON with payment data

## Domain Model Notes

- `CardToken`
  - Value object for token representation
  - Enforces prefix format (`tok_...`) in `fromString`
- `Payment`
  - Aggregate-like entity containing:
    - `id`, `token_id`, `amount`, `currency`, `status`, `webhook_url`
  - Behaviors:
    - `initiate(...)` creates `pending` payment
    - `process()` updates status to `success` or `failed`
    - `refund()` allows refund only from `success`

## Ports and Adapters

- **Ports (interfaces)**
  - `PaymentRepositoryInterface`
  - `WebhookSenderInterface`
- **Adapters (implementations)**
  - `JsonPaymentRepository` stores payments in `data/payments.json`
  - `HttpWebhookSender` posts payment event payload to webhook URL

This makes it straightforward to replace storage or webhook transport without changing use case logic.

## API Reference

### `POST /api/tokens`

Request body (JSON):

```json
{
  "card_number": "4242424242424242",
  "exp_month": "12",
  "exp_year": "2030",
  "cvc": "123"
}
```

### `POST /api/charges`

Request body (JSON):

```json
{
  "token": "tok_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "amount": 120.5,
  "currency": "USD",
  "user_id": "user_1",
  "webhook_url": "http://localhost:8000/api/payments/webhook"
}
```


## Running Locally

Requirements:

- PHP 8.1+

Optional (declared dependency):

- Composer package `guzzlehttp/guzzle` (current webhook sender uses cURL directly)

Start the server from `payment-provider`:

```bash
mkdir -p data
php -S localhost:9000 -t public
```

Then call endpoints at:

- `http://localhost:9000/api/tokens`
- `http://localhost:9000/api/charges`

