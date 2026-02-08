# Magento 2 Promo Hint Module

[![Latest Version](https://img.shields.io/github/v/release/spalenza/magento2-promo-cart-hint)](https://github.com/spalenza/magento2-promo-cart-hint/releases)
[![License](https://img.shields.io/github/license/spalenza/magento2-promo-cart-hint)](LICENSE)
[![Magento 2](https://img.shields.io/badge/magento-2.4-blue.svg)](https://magento.com)

A Magento 2 extension that displays applicable cart price rule promotions for products in the shopping cart. Uses asynchronous message queue processing for optimal performance.

## Features

- 🛒 **Promotion Display** - Shows applicable promotions for products in the cart
- ⚡ **Async Processing** - Uses message queue to prevent performance impact on admin
- 🏪 **Multi-Store Support** - Works across multiple store views
- 🎨 **Customizable Templates** - Flexible token-based text templates
- 📊 **Configurable Limits** - Control maximum number of promotions displayed
- 🔧 **Admin Configuration** - Easy-to-use admin panel settings
- 🌍 **Multi-Language** - Includes Portuguese (Brazil) translations

## Requirements

- Magento 2.4.x (tested on 2.4.8-p3)
- PHP 8.1 or higher
- MySQL/MariaDB
- Enabled message queues (db connection)

## Installation

### Via Composer (Recommended)

```bash
composer require spalenza/magento2-promohint
php bin/magento module:enable Spalenza_PromoHint
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

### From GitHub Archive

1. Download the latest release from [GitHub Releases](https://github.com/spalenza/magento2-promo-cart-hint/releases)
2. Extract to `app/code/Spalenza/PromoHint/`
3. Run the installation commands above

### From Zip File

1. Download the module zip file
2. Extract to `app/code/Spalenza/PromoHint/`
3. Run the installation commands above

## Configuration

### 1. Enable the Module

Navigate to **Stores > Configuration > Spalenza > Promo Hints**

### 2. Configure Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Enabled | Enable/disable the module | Yes |
| Display Text Template | Template for promo text with tokens | `%product_name% has the following promotion: %title%` |
| Maximum Rules to Display | Max promotions shown per product | 5 |

### 3. Available Template Tokens

You can use the following tokens in your display text template:

| Token | Description |
|-------|-------------|
| `%product_name%` | Product name |
| `%product_sku%` | Product SKU |
| `%title%` | Promotion title from Cart Price Rule |
| `%discount_amount%` | Discount amount |
| `%coupon_code%` | Coupon code (if applicable) |

**Example Template:**
```
🎉 Special offer: %product_name% qualifies for "%title%"! Save now with code: %coupon_code%
```

## Consumer Setup (Required)

The module uses message queues for async processing. You **must** run the consumer for the module to work properly.

### Development Mode

```bash
php bin/magento queue:consumers:start spalenzaPromohintRuleUpdateConsumer
```

### Production Deployment

Use a process manager like **Supervisor** to keep the consumer running:

#### Install Supervisor

```bash
# Ubuntu/Debian
sudo apt-get install supervisor

# CentOS/RHEL
sudo yum install supervisor
```

#### Configure Supervisor

Create a new configuration file: `/etc/supervisor/conf.d/spalenza-promohint-consumer.conf`

```ini
[program:spalenza-promohint-consumer]
command=php /var/www/html/bin/magento queue:consumers:start spalenzaPromohintRuleUpdateConsumer
directory=/var/www/html
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/spalenza-promohint-consumer.log
stopwaitsecs=60
```

#### Start the Consumer

```bash
# Reread supervisor configuration
sudo supervisorctl reread

# Update supervisor with new configuration
sudo supervisorctl update

# Start the consumer
sudo supervisorctl start spalenza-promohint-consumer:*

# Check status
sudo supervisorctl status spalenza-promohint-consumer:*
```

## Usage

1. Create a **Cart Price Rule** in Magento Admin (`Marketing > Cart Price Rules`)
2. The module automatically detects the rule and processes it asynchronously
3. When customers view their cart, applicable promotions are displayed at the top

## How It Works

```
┌─────────────────┐      ┌──────────────┐      ┌─────────────┐
│ Admin saves     │─────▶│ Observer     │─────▶│ Message     │
│ SalesRule       │      │ publishes    │      │ Queue       │
└─────────────────┘      └──────────────┘      └─────────────┘
                                                       │
                                                       ▼
┌─────────────────┐      ┌──────────────┐      ┌─────────────┐
│ Cart Page       │◀─────│ AJAX         │◀─────│ Consumer    │
│ displays hints  │      │ Controller   │      │ processes   │
└─────────────────┘      └──────────────┘      └─────────────┘
                                                        │
                                                        ▼
                                                 ┌─────────────┐
                                                 │ Products    │
                                                 │ extracted   │
                                                 │ from rules  │
                                                 └─────────────┘
```

### Technical Details

- **Observers**: Light-weight observers that only publish messages to the queue
- **Consumer**: Runs in background to process SalesRules and extract products
- **Database**: Stores promo hints in `spalenza_promo_hints` table
- **AJAX**: Cart page fetches hints asynchronously without blocking page load

## Troubleshooting

### Promotions not appearing?

1. **Check module is enabled**
   - Go to `Stores > Configuration > Spalenza > Promo Hints`
   - Ensure "Enabled" is set to "Yes"

2. **Verify the consumer is running**
   ```bash
   php bin/magento queue:consumers:list
   ```
   Look for `spalenzaPromohintRuleUpdateConsumer` in the list

3. **Check logs**
   ```bash
   tail -f var/log/spalenza_promohint.log
   ```

4. **Clear cache**
   ```bash
   php bin/magento cache:flush
   ```

5. **Reindex**
   ```bash
   php bin/magento indexer:reindex
   ```

### Consumer issues?

#### Test the consumer manually

```bash
# Run consumer with max messages limit for testing
php bin/magento queue:consumers:start spalenzaPromohintRuleUpdateConsumer --max-messages=100
```

#### Check queue tables

```sql
-- Check if queue exists
SELECT * FROM queue WHERE queue_name = 'spalenza-promohint-rule-update';

-- Check for pending messages
SELECT * FROM queue_message;
```

#### Restart Supervisor (if using)

```bash
sudo supervisorctl restart spalenza-promohint-consumer:*
```

### Module not installing?

1. **Check PHP version** (requires 8.1+)
   ```bash
   php -v
   ```

2. **Verify Magento version** (requires 2.4.x)
   ```bash
   php bin/magento --version
   ```

3. **Check for conflicts**
   ```bash
   php bin/magento module:status
   ```

4. **Enable module explicitly**
   ```bash
   php bin/magento module:enable Spalenza_PromoHint
   ```

## Database Schema

The module creates the following table:

### `spalenza_promo_hints`

| Column | Type | Description |
|--------|------|-------------|
| `entity_id` | int (PK) | Primary key |
| `rule_id` | int (FK) | References salesrule.rule_id |
| `product_id` | int (FK) | References catalog_product_entity.entity_id |
| `store_id` | int (FK) | References store.store_id |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |

**Indexes**: rule_id, product_id, store_id
**Constraints**: UNIQUE(rule_id, product_id, store_id)

## API

### Service Class

The main service class is `Spalenza\PromoHint\Model\Service\PromoHintService`

**Public Methods:**

```php
// Check if module is enabled
public function isEnabled(): bool

// Get display text template
public function getDisplayTextTemplate(): string

// Get maximum rules to display
public function getMaxRulesDisplay(): int

// Get promo hints for products
public function getPromoHintsForProducts(array $productIds, int $storeId): array

// Replace tokens in template
public function replaceTokens(string $template, array $data): string
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## Support

- **Documentation**: [Full Documentation](https://github.com/spalenza/magento2-promo-cart-hint/wiki)
- **Issues**: [GitHub Issues](https://github.com/spalenza/magento2-promo-cart-hint/issues)
- **Email**: support@spalenza.com

## Changelog

### 1.0.0 (2024-02-08)

- Initial release
- Display applicable promotions in shopping cart
- Async message queue processing
- Multi-store support
- Admin configuration panel
- Portuguese (Brazil) translations

## License

This module is licensed under the [MIT License](LICENSE).

```
MIT License

Copyright (c) 2024 Spalenza

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

## Credits

Developed by [Denis Spalenza](https://github.com/deniscsz)

---

**Made with ❤️ for Magento 2 Community**
