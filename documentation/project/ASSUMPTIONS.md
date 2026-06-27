---
layout: default
title: Assumptions and Limitations
---

# Assumptions and Limitations

## Assumptions

- The API is the only application included in the assessment.
- JWT is used for stateless API authentication.
- Product prices and order totals use fixed database precision.
- Currency records represent supported transaction currencies; live exchange rates are not required.
- Order statuses and payment statuses are stored as numeric enum values.
- Payment methods contain a strategy key used to select a gateway implementation.
- Payment processing is simulated.
- Seeders provide enough data to test the main workflows.
- API consumers send and receive JSON.

## Limitations

- No real payment provider credentials are used.
- No actual card information should be collected or stored.
- Payment simulation does not guarantee real-world gateway behavior.
- Refunds, partial captures, and chargebacks are not included.
- Currency conversion is not performed unless explicitly implemented in the module.
- The project does not provide a frontend.
- Production deployment, monitoring, and horizontal scaling are outside the assessment scope.

## Security Notes

- Do not commit `.env`.
- Do not publish JWT secrets or database credentials.
- Do not place sensitive examples in the public GitHub Pages site.
- GitHub Pages documentation is public when published, so assessment credentials must not be documented.
- Real card numbers must never be used in this simulated project.

## Design Trade-offs

### Service–Repository Pattern

This adds more files than a small CRUD project, but it keeps controllers thin and supports clearer testing and replacement of persistence logic.

### Strategy Pattern

This adds a gateway contract and resolver, but it avoids large conditional blocks inside the Payment service and makes new gateways easier to add.

### Numeric Enums

Numeric enums provide compact and stable database values. API resources should return readable labels when clients need human-friendly output.
