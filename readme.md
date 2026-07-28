# Openings

Manage opening hours for one or more locations/departments in the backend and
display them via shortcode.

## Usage

```
[plugin=openings]location_id=1[/plugin]
```

The location ID is shown in the location list in the backend (Addons > Openings).

## Features

- Unlimited locations (e.g. multiple shops, branches or departments), each with its
  own weekly schedule and its own shortcode
- One row per weekday (Monday - Sunday) per location, each independently markable
  as closed
- Multiple time ranges per day (e.g. a morning and afternoon slot separated by a
  lunch break), entered as one `HH:MM-HH:MM` line per range
- Optional intro text (above the table) and note text (below the table, e.g. for
  holiday notices), configurable per location
- Optional highlighting of the current weekday's row on the frontend, per location
