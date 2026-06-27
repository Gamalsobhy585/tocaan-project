---
layout: default
title: Project Scope
---

# Project Scope

## Purpose

This project is a backend technical assessment for Tocaan Company. It demonstrates the design and implementation of a modular Laravel 13 API for authentication, master data, orders, and payment integration.

## In Scope

### Authentication

- User registration
- User login
- JWT token generation
- Authenticated-user endpoint
- Token refresh, when implemented by the authentication module
- Logout or token invalidation, when supported by the configured JWT package

### Product Master Data

- Create products
- Update products
- Delete products
- List and view products
- Store product codes, localized names, stock quantities, and unit prices

### Currency Master Data

- Create currencies
- Update currencies
- Delete currencies
- List and view currencies
- Store supported currency codes and related master-data fields

### Order Management

- Create an order
- Create order items
- Update an order
- Delete an order
- View one order
- List orders
- Filter orders by numeric status enum
- Calculate or store order totals according to the module rules
- Return related product information for order items

### Payment Methods

- Store available payment methods
- Map a payment method to a gateway strategy key
- Enable adding new payment methods without changing unrelated modules

### Payment Processing

- Create a payment for an order
- Simulate payment processing
- Store payment status using a numeric enum
- Support pending, successful, and failed states
- Select the correct gateway strategy from the payment method
- Retrieve all payments
- Retrieve payments for a specific order
- Store transaction references, gateway responses, and failure reasons when available

### Documentation

- Installation instructions
- Project scope
- Architecture explanation
- Module documentation
- Postman workspace reference
- Testing instructions
- GitHub Pages documentation site

## Out of Scope

The following items are intentionally outside this assessment unless separately implemented:

- Frontend or mobile application
- Real credit-card charging
- Real PayPal transactions
- PCI-DSS compliance implementation
- Refunds and chargebacks
- Recurring subscriptions
- Exchange-rate synchronization with an external provider
- Warehouse reservation workflows
- Shipping and delivery modules
- Production infrastructure and monitoring
- Full multi-tenant support
- Advanced roles and permissions

## Payment Simulation Rule

Payment gateways return simulated results. Their purpose is to demonstrate clean integration boundaries and extensibility, not to transfer real funds.

## Success Criteria

The assessment is successful when:

1. The project can be installed from the documented steps.
2. Authentication returns a usable JWT token.
3. Master-data endpoints work correctly.
4. Orders can be created with order items.
5. Orders can be filtered by status.
6. Payments are linked to orders.
7. The selected payment method triggers the correct gateway strategy.
8. A new gateway can be added without modifying core payment-processing logic.
9. API requests are available in the Postman workspace.
10. The documentation can be published through GitHub Pages.
