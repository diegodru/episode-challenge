# What potential actions do you identify as requiring a recalculation of positions in the Parts linked to the Episode?

Recalculating positions would be required when operating on the position of any
register. Whether it is inserting, deleting, or persistent sorting.

The method used when a position is provided when inserting a part depends on
whether the part is meant to go at the beginning, the end, or in between any
already placed parts.

If the part is placed:

* At the beginning: Then the position of each part of the list must be shifted
  +1 (or -1 when deleting), then register the part with position 0.

* At the end: Then the part can be registered with the next value following the
  highest part position already registered.

* In between other parts: Then every other part with a higher or equal position
  value needs to be shifted +1 (or -1 when deleting).

# What API Request payload structure would you use to reliable place the Parts in the position they should end up on? Consider separate endpoints to add, delete and sort Parts.

## The Add Method

The Add method

The following would be the expected payload:

```JSON
{
    "part": {
        ...data,
        "position": <position> //optional
    }
}
```

## The Delete Method

If the structure requires for every part's position to follow a sequence, then
the implementation would require to iterate over every part following the
deleted part's position and decrease each position by 1. This approach could
reach linear time O(n) in the worst case where the first position is deleted.

On the other hand, if the position is solely used to return a sort order, the
implementation can achieve constant time O(1) by just deleting the register.

Deleting


## The Sort Method

> [!NOTE]
> The sort method is assumed to allow the user to reposition parts by providing
> an ordered array rather than sort by executing a closure (or by providing an
> expression). This enables the actual sorting by analyzing content be done
> locally on the user's end to then send the result to the server.

To handle sorting with the developer experience in mind, the expected payload
could include an array with the current position values (or the parts'
composite ids) of the parts wished to sort in the desired order. The payload
needn't to include all parts of an episode, only the parts wished to be
repositioned. The server would be responsible to update the position field of
the parts included in the array with the index in which its current position
value (or the part's composite id) is found within the array.

> [!IMPORTANT]
> The remaining parts not included in the array would have their position field
> updated to the value returned by a cached integer (with its starting value
> equal to the length of the array) and increased by one with each subsequent
> update.

> [!NOTE]
> Any repeated values in the provided array will only have its first appearance
> counted while any other will be ignored

> [!WARNING]
> This approach would be stochastic as each call would with the same body would
> not yield the same order.

```JSON
{
  "positions": [
    2,
    7,
    0,
    1
  ]
}
```

A deterministic approach could use the parts' identifiers, like so:

> [!NOTE]  
> Identifiers are represented as characters for a visual distinction to positions

```JSON
{
  "ids": [
    "k",
    "a",
    "j",
    "c"
  ]
}
```

Another possible implementation could involve other fields stored in the part
register. Similar to how query languages operate, the server could be set to
listen on an endpoint and expect which column to order by. Including ascending
or descending order.

This would allow the sorting to be done on the server side.

```JSON
{
    "order_by": <field_name>,
    "sort_order": <ASC|DSC>
}
```

# Implement the calculation of the new positions for Parts when changes are performed that affect them.

Code examples are provided in the [index.php](./index.php) file.

# Do you have another approach on how this can be tackled efficient and reliably at scale?

## Queuing method calls

Asynchronously queuing methods for when a large requests come in can improve
developer experience as a job can be queued while other operations are handled
on the client side.

## Attempt to insert and delete in constant time O(1)

Worst case scenario, an insertion at the beginning would require to iterate
over every part of an episode to update the position, making the time it takes
to insert depend on the number of parts registered O(n). This could be costly
if the system has episodes with a significant number of parts registered.

To reduce the potentially costly lapse to insert, a restructure of the data can
be considered. The implementation could rely on the following:

### Implement Positions using Floating Points

The use of floating points rather than integers for the position field could
achieve constant time when inserting a new part. Returning only an ordered
array of parts with their data and abstracting the positioning structure on the
server side.

> [!WARNING]
After several in-between insertions within the same range, the gaps may become
small enough to overflow the mantissa. To handle this, the indices can be
normalized by recalculating new evenly spaced positions. This would be
considered a rare operation and only necessary when possible values between the
gap have been exhausted.

### A Doubly Linked list structure with a map representation

Another approach for an attempt at constant time when inserting/deleting can
include a doubly linked list-like structure rather than registering positions.
Returning an object upon a request instead of a list of parts. Each key is the
unique identifier to a map for the part data as its value, which in part (no pun
intended) has keys (i.e. `next` and `prev`) containing the id of the following
and previous part as their values. 

The user can then parse the structure as a hash map (dictionary) to easily
iterate over the parts in the intended order.

> [!WARNING]
> This method would place the burden of sorting onto the user who would need
> to locally restructure the data and cache it for further efficient access.
> Moreover, this method would hinder performance for smaller systems as hashing
> identifiers would be more expensive than iterating over a small array.

The structure for a requested episode would follow a structure like the following:

```JSON
{
    "a": {
        ...data,
        "prev": null,
        "next": "d"
    },
    "b": {
        ...data,
        "prev": "c",
        "next": null
    },
    "c": {
        ...data,
        "prev": "d",
        "next": "b"
    },
    "d": {
        ...data,
        "prev": "a",
        "next": "c"
    }
}
```

To make finding the last part more efficient, the `next` and `prev` fields
would be indexed for a quick lookup which would make finding the register with
its `next` or `prev` field set to null more efficient.

The insertion would need to be reimplemented. Rather than provide a
position to where the part should be inserted, the user would provide the id of
the part that should link to the newly added part (with either `next` or `prev` set)

## Other ideas that could increase efficiency depending on use cases

* Use a fast in-memory temporary database for real time collaboration with
  session management
* The use of concurrency with a divide and conquer approach
* Avoid database connection round trips by implementing batch operators
