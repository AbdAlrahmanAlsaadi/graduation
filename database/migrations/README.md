# Migrations Notes

## Work Item Details (EAV)

The `work_item_details` table stores optional, variable attributes for work items
as key/value pairs. This keeps the core work_items table stable while allowing
new detail fields without schema changes.

Convert EAV details into structured columns or dedicated tables when:

- a key becomes required or heavily queried
- you need strict typing, constraints, or reporting performance
- multiple keys always appear together and form a real domain entity
