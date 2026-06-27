---
layout: default
title: Tocaan Backend Assessment
---

# Tocaan Backend Assessment API

This documentation describes the Laravel 13 REST API created for the Tocaan Company technical assessment.

## Project Overview

The system provides:

- JWT authentication
- Product and currency master data
- Order and order-item management
- Order status filtering
- Payment-method configuration
- Simulated payment processing
- Extensible gateway integration through the Strategy pattern

The source code is divided into independent modules. Each main module uses Service and Repository layers, while payment processing also uses gateway strategies.

## Documentation Navigation

### Getting Started

- [Installation Guide](INSTALLATION.md)
- [Project Scope](PROJECT_SCOPE.md)
- [Architecture](ARCHITECTURE.md)
- [API Reference](API_REFERENCE.md)
- [Testing Guide](TESTING.md)
- [Assumptions and Limitations](ASSUMPTIONS.md)

### Module Documentation

- [Authentication Module](AUTHENTICATION_MODULE.md)
- [Product Master Data](PRODUCT_MASTER_DATA.md)
- [Currency Master Data](CURRENCY_MASTER_DATA.md)
- [Order Module](ORDER_MODULE.md)
- [Payment Module](PAYMENT_MODULE.md)

## Postman Workspace

Use the public Postman workspace to inspect and execute the API requests:

[Open Tocaan Postman Workspace](https://martian-shadow-736975.postman.co/workspace/1fe9452d-f7b9-4ca3-a0a5-8e15ccfa4db8)

## Local API Base URL

```text
http://127.0.0.1:8000/api
```
