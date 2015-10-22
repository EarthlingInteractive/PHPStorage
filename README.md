[![Build Status](https://travis-ci.org/EarthlingInteractive/PHPStorage.svg)](https://travis-ci.org/EarthlingInteractive/PHPStorage)

# PHP Storage

A data access layer.

Goals:

- Glean schema information from a EarthIT_Schema object.

- Provide a consistent API for fetching from and storing into a
  database or databases.

- Objects passed in and out are in 'schema form'; transformations
  to database form are done by the storage layer.

- Allow custom storage layers to do additional transformations.

Design principles:

- Excplicitly state which representations of objects are used where,
  rather than relying on coincidental similarities between them.
  - e.g. a function that takes an object should indicate in its
    documentation which form that object should be in.

- Data is separate from the plumbing.  Data objects are just arrays
  (or, conceivably, scalar values.  But usually arrays).

- Backend agnostic.  May be RDB.  May be something else entirely.

- Allow efficient access to collections of objects of the same type.
  - Functions should generally be written to operate on collections to
    amortize overhead costs.

- Should be able to use query generation and object conversion utilities
  independently.

- Punting on multi-table joins for now.
  Easier to just do the joins in PHP by querying by sets of IDs,
  especially when links cross database boundaries.


## Object representations

- DB-internal form: actual bits stored in the database.
  Our PHP code never sees these.

- DB-external form:
  
  Values from the database with minimal transformations applied to
  make them representable in PHP.  e.g.
  
  - BIGINTs are represented as strings
  - GEOMETRY values are represented as GeoJSON strings
  
  Keys correspond exactly to column names.

- Schema form: form as described in schema.txt.
  - GEOMETRY fields are JSON-decoded
  - keys are 'plain english' field names.

- JSO form:
  
  Standard form for JSON REST services.
  Values will usually be the same as those in schema form,
  but keys will be 'camelCase' instead of 'plain english'.
  
  - "JSO" is not a typo.  Objects in this form are the objects that
    would be JSON-encoded without JSON encoding yet applied, hence no
    'N'.
  - 'id' field is added to objects that have composite keys.
  - Transforming to/from this form is out of the scope of this library.
