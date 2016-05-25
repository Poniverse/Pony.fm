Developing notifications for Pony.fm
====================================

Pony.fm's notification system is designed around "drivers" for various
notification delivery methods. The types of notification one can receive
are defined in the
[`NotificationHandler`](app/Contracts/NotificationHandler.php)
interface, which is implemented by every class that needs to know about
the various notification types.


Sending a notification
----------------------

The `Notification` facade is used to send notifications as follows:

```php
use Notification;

// Something happens, like a  newtrack getting published.
$track = new Track();
...

// The "something" is done happening! Time to send a notification.
Notification::publishedTrack($track);
```

This facade has a method for every notification type, drawn from the
[`NotificationHandler`](../app/Contracts/NotificationHandler.php) interface.
Each of these methods accepts the data needed to build a notification
message and a list of the notification's recipients.


Adding new notification types
-----------------------------

1. Add a method for the new notification type to the
   [`NotificationHandler`](../app/Contracts/NotificationHandler.php)
   interface.
   
2. Implement the new methods in every class that implements the
   interface. Use your IDE to find these. An inexhaustive list:
   
   - [`NotificationManager`](../app/Library/Notifications/NotificationManager.php)
   - [`RecipientFinder`](../app/Library/Notifications/RecipientFinder.php)
   - [`PonyfmDriver`](../app/Library/Notifications/PonyfmDriver.php)

3. Call the new method on the `Notification` facade from wherever the
   new notification gets triggered.
   
4. Implement any necessary logic for the new notification type in the
   [`Activity`](../app/Models/Activity.php) model.


Adding new notification drivers
-------------------------------

1. Create a new class for the driver that implements the
   [`NotificationHandler`](../app/Contracts/NotificationHandler.php)
   interface.

2. Make each method from the above interface send the corresponding type
   of notification to everyone who is to receive it via that driver.
   Implement UI and API integrations as needed.
   
3. Modify the
   [`RecipientFinder`](../app/Library/Notifications/RecipientFinder.php)
   class to build recipient lists for the new driver.
   

Architectural notes
-------------------

The notification system is designed around two ideas: being as type-safe
as PHP allows it to be, and doing all the processing and sending of
notifications asynchronously.

To that end, the
[`NotificationManager`](../app/Library/Notifications/NotificationManager.php)
class is a thin wrapper around the `SendNotifications` job. The job
calls the notification drivers asynchronously to actually send the
notifications. This job should run on a dedicated queue in production.

The [`NotificationHandler`](../app/Contracts/NotificationHandler.php)
interface is key to maintaining type safety - it ensures that drivers
and `NotificationManager` all support every type of notification. All
classes that have logic specific to a notification type implement this
interface to ensure that all notification types are handled.

There's one exception to the use of `NotificationHandler` - the 
[`Activity`](../app/Models/Activity.php) model. The logic for mapping the
data we store about an activity in the database to a notification's API
representation had to go somewhere, and using the `NotificationHandler`
interface here would have made this logic a lot more obtuse.
