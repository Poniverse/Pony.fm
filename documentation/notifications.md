Developing notifications for Pony.fm
====================================

Pony.fm's notification system is designed to support various notification
delivery methods. The types of notification one can receive are defined in the
[`NotificationHandler`](app/Contracts/NotificationHandler.php)
interface, which is implemented by every class that needs to know about
the various notification types.


Sending a notification
----------------------

The `Notification` facade is used to send notifications as follows:

```php
use Notification;

// Something happens, like a  new track getting published.
$track = new Track();
...

// The "something" is done happening! Time to send a notification.
Notification::publishedNewTrack($track);
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

3. Create a migration to add the new notification type to the `activity_types`
   table. Add a constant for it to the [`Activity`](../app/Models/Activity.php)
   class.

3. Ensure you create HTML and plaintext templates, as well as a subclass of
   [`BaseNotification`](../app/Mail/BaseNotification.php) for the email version
   of the notification.

4. Call the new method on the `Notification` facade from wherever the
   new notification gets triggered.
   
5. Implement any necessary logic for the new notification type in the
   [`Activity`](../app/Models/Activity.php) model.


Adding new notification delivery methods
----------------------------------------

1. Implement a method for sending notifications via the new delivery method in
   the [`PonyfmDriver`](../app/Library/Notifications/PonyfmDriver.php) class.
   Use how email delivery is implemented as a guide.

2. Add UI for subscribing and unsubscribing to the delivery method to the
   [`account settings area`](../public/templates/account/settings.html).
   

Architectural notes
-------------------

The notification system is designed around two ideas: being as type-safe
as PHP allows it to be, and doing all the processing and sending of
notifications asynchronously.

To that end, the
[`NotificationManager`](../app/Library/Notifications/NotificationManager.php)
class is a thin wrapper around the `SendNotifications` job. The job
calls the notification logic asynchronously to actually send notifications. This
job should run on a dedicated queue in production.

The [`NotificationHandler`](../app/Contracts/NotificationHandler.php)
interface is key to maintaining type safety - it ensures that many classes
associated with notifications all support every type of notification. Classes
that have logic specific to a notification type implement this interface to
ensure that all notification types are handled.

Furthermore, the `activity_types` table is used to provide referential data
integrity in the database - all notifications are linked to an activity record,
and each activity record must correspond to a valid activity type. This table is
also used for validation of users' subscription preferences.

There's one exception to the use of `NotificationHandler` - the 
[`Activity`](../app/Models/Activity.php) model. The logic for mapping the
data we store about an activity in the database to a notification's API
representation had to go somewhere, and using the `NotificationHandler`
interface here would have made this logic a lot more obtuse.

### Data flow

1. Some action that triggers a notification calls the `NotificationManager`
   facade.
   
2. An asynchronous job is kicked off that figures out how to send the
   notification.

3. An `Activity` record is created for the action.

4. A `Notification` record is created for every user who is to receive a
   notification about that activity. These records double as Pony.fm's on-site
   notifications and cannot be disabled.

5. Depending on subscription preferences, push and email notifications will be
   sent out as well, each creating their own respective database records. These
   are linked to a `Notification` record for unified read/unread tracking.

6. A `Notification` record is marked read when it is viewed on-site or any other
   notification type associated with it (like an email or push notification) is
   clicked.
