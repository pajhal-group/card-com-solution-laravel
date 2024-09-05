# Cardcom Payment Gateway Integration in Laravel

This repository demonstrates how to integrate the **Cardcom** payment gateway into a Laravel application using **Guzzle** for handling API requests and XML responses.

## Prerequisites

- Laravel 8.x or 9.x installed
- PHP 7.4 or higher
- Composer installed
- Guzzle HTTP client
- Cardcom API credentials (Terminal, Username, API Name, API Password)

## Features

- Secure payment integration with Cardcom
- Handling payments through Cardcom API
- XML-based API requests and responses
- Laravel service to handle payment requests and responses
- Basic payment form for order processing

## Installation

1. **Clone the repository:**

   ```bash
   git clone https://github.com/pajhal-group/card-com-laravel.git
   cd card-com-laravel
   ```

2. **Install dependencies:**

   ```bash
   composer install
   ```

3. **Configure environment:**

   Copy the `.env.example` file to `.env` and add your Cardcom API credentials:

   ```bash
   cp .env.example .env
   ```

   Set your Cardcom credentials and pass inside app/Providers/Cardcomservice.php:

   ```bash
   CARDCOM_TERMINAL=your-terminal-id
   CARDCOM_USERNAME=your-username
   CARDCOM_API_NAME=your-api-name
   CARDCOM_API_PASSWORD=your-api-password
   ```

4. **Run migrations (if needed):**

   ```bash
   php artisan migrate
   ```

5. **Serve the application:**
   ```bash
   php artisan serve
   ```

## Usage

- Access the payment form at `/payment`.
- Submit the form to process a payment using the Cardcom API.
- View transaction status and response messages in real-time.

## Implementation

The Cardcom payment flow involves:

1. **Creating a payment request:** This is handled by the `CardcomService` using the Guzzle HTTP client.
2. **Sending XML requests and receiving responses:** The API responses are processed to determine payment success or failure.
3. **Handling callbacks:** Cardcom sends callbacks to notify about the transaction status.

## Configuration

You can adjust the Cardcom configuration or add custom logic in the `CardcomService` located in `app/Services/CardcomService.php`.

## Testing

For testing in sandbox mode:

- Ensure you have sandbox credentials from Cardcom.
- Test the entire payment process before switching to production.

## Contributing

Feel free to submit issues or pull requests for improvements or bug fixes.

## License

This project is licensed under the MIT License.
