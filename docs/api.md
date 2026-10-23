# REST API reference — `/api/v1`

Headers: `Accept: application/json`, `Authorization: Bearer <token>` (all routes except `auth/*` and `countries*`).

## Envelope
```json
{ "success": true, "message": "Wallet.", "data": { }, "meta": { }, "links": { } }
{ "success": false, "message": "The given data was invalid.", "errors": { "amount": ["…"] } }
```
Lists add `meta` (`current_page`, `per_page`, `total`, `last_page`) and `links` (`next`, `prev`). Use `?per_page=1..100&page=n`.
Money is a string with two decimals: `"67521.00"`. Send amounts as strings or numbers with ≤ 2 decimals.

| Status | Meaning |
|---|---|
| 200 / 201 | OK / created |
| 401 | missing or invalid token |
| 403 | suspended account |
| 404 | not found **or not yours** |
| 422 | validation or business-rule failure (`errors` populated) |
| 429 | throttled (`auth` 10/min, `money` 30/min, `api` 120/min per user/IP) |

## Auth
| Method | Path | Body |
|---|---|---|
| POST | `auth/register` | `name, email, password, password_confirmation, device_name?` → `user, access_token` |
| POST | `auth/login` | `email, password, device_name?` |
| POST | `auth/verify-email` | `email, token` (64-char token from the e-mailed link; single use) |
| POST | `auth/resend-verification` | `email` (same answer for unknown/verified addresses; 60 s cooldown) |
| POST | `auth/forgot-password` | `email` (same answer for unknown emails) |
| POST | `auth/reset-password` | `token, email, password, password_confirmation` (revokes all API tokens) |
| PUT | `auth/password` | `current_password, password, password_confirmation` (bearer; signs out other sessions) |
| GET | `auth/me` · POST `auth/logout` | |

**Verified e-mail gate.** Money endpoints (invest, withdraw, loan apply/pay/cancel) return `403` with the standard envelope until the address is verified (`KEYSTEAD_REQUIRE_VERIFIED_EMAIL`). `UserResource.email_verified` tells a client which state it is in.

**Idempotency.** Send `Idempotency-Key: <unique string, ≤128 chars>` on a money `POST`. A retry with the same key and body replays the stored response without repeating the operation; the same key with a different body is `422`; a retry while the first call is still running is `409`. Keys are kept 24 h (`idempotency:prune`).

## Reference & profile
`GET countries` · `GET countries/{id}/states` (public) · `GET|PUT profile` (`first_name, last_name, gender, dob, phone, occupation, address, city, country_id, state_id`).

## Wallet
| Method | Path | Notes |
|---|---|---|
| GET | `wallet` | `{ id, balance, currency }` |
| GET | `portfolio` · `financial-profile` · `earnings` | own data only |
| GET | `transactions?type=&status=` · `transactions/{id}` | ledger, newest first |
| GET | `withdrawals` · POST `withdrawals` `{ amount }` | funds reserved; admin decides in the console |

## Investments
| Method | Path | Notes |
|---|---|---|
| GET | `investment-projects` · `/{id}` · `/{id}/profit-history` | open projects with `remaining_amount` |
| GET | `investments` · `investments/summary` | own positions |
| POST | `investments` `{ investment_project_id, amount }` | min / remaining / balance / status enforced |
| GET | `profit-history` | own profit shares |

## Loans (`{type}` = `mortgage` | `property`)
| Method | Path | Notes |
|---|---|---|
| GET | `loans/{type}/products` | active products |
| GET / POST | `loans/{type}` | POST `{ loan_project_id, initial_payment }` — must equal the product minimum |
| GET | `loans/{type}/{id}` | includes payments |
| POST | `loans/{type}/{id}/payments` | optional `{ amount }` must equal the installment due; one per month |
| POST | `loans/{type}/{id}/cancel` | pending only; refunds the initial payment |

Approving/rejecting loans, distributing profit, deciding withdrawals and funding wallets are **staff-console** actions, not API endpoints.
