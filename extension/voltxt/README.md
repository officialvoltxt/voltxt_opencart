# Voltxt Solana Payment Gateway for OpenCart 4.x

A cryptocurrency payment gateway extension for OpenCart 4.x that allows customers to pay with Solana (SOL) cryptocurrency through the Voltxt platform.

## Features

- Accept Solana cryptocurrency payments
- Secure payment processing through Voltxt platform
- Support for both testnet and mainnet
- Webhook integration for real-time payment updates
- Configurable order statuses
- Geo-zone restrictions support
- Debug logging

## Requirements

- OpenCart 4.x
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Valid Voltxt API key

## Installation

### 1. Install Extension

1. Upload the extension files to your OpenCart installation
2. Go to Admin → Extensions → Extensions
3. Find "Voltxt Solana Payment Gateway" and click Install
4. Click Edit to configure the extension

### 2. Database Tables

The extension automatically creates the required database tables during installation. No manual SQL execution is needed.

### 3. Configure Extension

1. Go to Admin → Extensions → Extensions → Payments
2. Find "Voltxt Solana Payment Gateway" and click Edit
3. Enter your Voltxt API key
4. Configure network settings (testnet/mainnet)
5. Set order status mappings
6. Configure geo-zone restrictions
7. Save settings

### 4. Test Configuration

1. Use the "Test Connection" button to verify your API key
2. Make a test order to ensure the payment flow works
3. Check that webhooks are being received

## Configuration Options

### API Settings
- **API Key**: Your Voltxt platform API key
- **Network**: Choose between testnet and mainnet
- **Expiry Hours**: How long payment sessions remain valid

### Order Statuses
- **Pending Status**: Status when payment is initiated
- **Processing Status**: Status when payment is completed
- **Cancelled Status**: Status when payment is cancelled
- **Failed Status**: Status when payment fails

### Restrictions
- **Geo Zone**: Restrict payment method to specific regions
- **Minimum Amount**: Minimum order amount for payment
- **Maximum Amount**: Maximum order amount for payment
- **Supported Currencies**: Currencies that support this payment method

## File Structure

```
extension/voltxt/
├── admin/
│   ├── controller/extension/payment/voltxt.php
│   ├── language/en-gb/extension/payment/voltxt.php
│   └── view/template/extension/payment/voltxt.twig
├── catalog/
│   ├── controller/extension/payment/voltxt.php
│   ├── language/en-gb/extension/payment/voltxt.php
│   ├── model/extension/payment/voltxt.php
│   └── view/template/extension/payment/voltxt.twig
├── system/
│   └── library/voltxt_api_client.php
├── install.json
├── install.xml
└── README.md
```

## Troubleshooting

### Common Issues

1. **"Page Not Found" Error**: Ensure the extension is properly installed and the controller paths are correct
2. **API Connection Failed**: Verify your API key and network settings
3. **Payment Not Processing**: Check webhook configuration and ensure database tables were created
4. **Order Status Not Updating**: Verify order status IDs in configuration

### Debug Mode

Enable debug mode in the extension settings to get detailed logging information. Check your OpenCart error logs for Voltxt-related messages.

## Support

For support and documentation, visit [https://voltxt.io](https://voltxt.io)

## License

This extension is provided by Voltxt for use with OpenCart installations.

## Changelog

### Version 1.0.0
- Initial release
- OpenCart 4.x compatibility
- Solana payment processing
- Webhook integration
- Admin configuration interface
