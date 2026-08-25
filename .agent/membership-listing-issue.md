## Problem
The API exposes membership lifecycle mutations through:

- `PATCH /halaqas/{halaqaId}/memberships/{membershipId}`
- `DELETE /halaqas/{halaqaId}/memberships/{membershipId}`

However, `GET /halaqas/{halaqaId}/students` returns student users only and does not expose `membership.id`. A compliant client cannot reliably discover the membership identifier after refresh, so it cannot perform lifecycle actions without guessing or using undocumented behavior.

## Proposed backward-compatible solution
Add a dedicated, authorized, paginated endpoint:

`GET /halaqas/{halaqaId}/memberships`

The response returns a `MembershipCollectionResponse` whose items use the existing `Membership` schema, including `id`, `halaqa_id`, `student`, `status`, and `joined_at`.

The existing `/students` endpoint remains unchanged.

## Acceptance criteria
- [ ] The halaqa owner can list memberships for their own halaqa.
- [ ] The response includes `membership.id` for every item.
- [ ] Pagination and membership-status filtering are documented and tested.
- [ ] Unauthorized or non-owner access returns `403` with no data leakage.
- [ ] The OpenAPI contract and developer documentation are updated.
- [ ] Existing `GET /halaqas/{halaqaId}/students` behavior remains unchanged.
- [ ] Feature and authorization tests pass in CI.
