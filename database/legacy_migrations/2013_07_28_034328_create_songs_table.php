<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use Illuminate\Database\Migrations\Migration;

class CreateSongsTable extends Migration
{
    public function up()
    {
        Schema::create('show_songs', function ($table) {
            $table->increments('id');
            $table->string('title', 100);
            $table->text('lyrics');
            $table->string('slug', 200)->indexed();
        });

        Schema::create('show_song_track', function ($table) {
            $table->increments('id');
            $table->integer('track_id')->unsigned();
            $table->integer('show_song_id')->unsigned();

            $table->foreign('track_id')->references('id')->on('tracks')->on_update('cascade');
            $table->foreign('show_song_id')->references('id')->on('show_songs')->on_update('cascade');
        });

        DB::table('show_songs')->insert([
            'id' => 1,
            'title' => 'Equestria Girls',
            'lyrics' => "[Pinkie Pie]
Ooh! This is my jam!

There is a place
Where the grass is what's for dinner
Applejack: Soup's on, everypony!
[Pinkie Pie]
Charmed, fun, and wild
Applejack: Yeehaw!
[Pinkie Pie]
There must be something in the water
Spike: What?
[Pinkie Pie]
Sippin' rainbow juice
Talking Elements of Harmony
Rarity, Twilight, Applejack: Yeah!
[Pinkie Pie]
Our Bronies

Braeburn: Hey there!

[Pinkie Pie]
Hang out too

Ponies: (laughing)
Spike: Come on, Bronies!

[Pinkie Pie]
'Cause they know we're awesome fillies
Spike: Come on, everypony!

[Pinkie Pie]
You could travel the world (You could travel the world)
But no one can groove like the girls with the hooves
Once you party with ponies

Spike: Party with the ponies

[Pinkie Pie]
You'll be seeing Rainbooms!
O-oh o-oh o-ooh!

(Chorus)
[Pinkie Pie]
Equestria girls, we're kinda magical
Spike: Equestria!

[Pinkie Pie]
Boots on hooves, bikinis on top
Furry coats, so cute
We'll blow your mind
Aoaoah oh, aoaoaoh!
Equestria girls, we're pony-fabulous

Spike: Equestria!

[Pinkie Pie]
Fast, fine, fierce, we trot till we drop (Rarity: Oh!)
Cutie marks represent

Spike: Cutie marks!

[Pinkie Pie]
Now put your hooves up

Spike: Put yo' hooves in the air

[Pinkie Pie]
Aoaoah oh, aoaoaoh!

[Male Backup/Spike]
Break it down, DJ Pon-3
These are the ponies I love the most
I wish you could all be Equestria girls",
            'slug' => 'equestria-girls',
        ]);
        DB::table('show_songs')->insert([
            'id' => 2,
            'title' => 'My Little Pony Theme Song',
            'lyrics' => "[Backup singer]
My Little Pony, My Little Pony
Ahh, ahh, ahh, ahhhâ€¦..

[Twilight Sparkle]
(My Little Pony)
I used to wonder what friendship could be
(My Little Pony)
Until you all shared its magic with me

[Rainbow Dash]
Big adventure

[Pinkie Pie]
Tons of fun

[Rarity]
A beautiful heart

[Applejack]
Faithful and strong

[Fluttershy]
Sharing kindness

[Twilight Sparkle]
It's an easy feat
And magic makes it all complete
You have my little ponies
Do you know you're all my very best friends?",
            'slug' => 'my-little-pony-theme-song',
        ]);
        DB::table('show_songs')->insert([
            'id' => 3,
            'title' => 'Laughter Song',
            'lyrics' => "[Pinkie Pie]
When I was a little filly and the sun was going down...

Twilight Sparkle: Tell me she's not...

[Pinkie Pie]
The darkness and the shadows, they would always make me frown

Rarity: She is.

[Pinkie Pie]
I'd hide under my pillow
From what I thought I saw
But Granny Pie said that wasn't the way
To deal with fears at all

Rainbow Dash: Then what is?

[Pinkie Pie]
She said: \"Pinkie, you gotta stand up tall
Learn to face your fears
You'll see that they can't hurt you
Just laugh to make them disappear.\"

Ha! Ha! Ha!

Ponies: [gasp]

[Pinkie Pie]
So, giggle at the ghostly
Guffaw at the grossly
Crack up at the creepy
Whoop it up with the weepy
Chortle at the kooky
Snortle at the spooky

And tell that big dumb scary face to take a hike and leave you alone and if he thinks he can scare you then he's got another thing coming and the very idea of such a thing just makes you wanna... hahahaha... heh...

Laaaaaaauuugh!",
            'slug' => 'laughter-song',
        ]);
        DB::table('show_songs')->insert([
            'id' => 4,
            'title' => "Pinkie's Gala Fantasy Song",
            'lyrics' => "[Pinkie Pie]
Oh the Grand Galloping Gala is the best place for me
Oh the Grand Galloping Gala is the best place for me
Hip hip
Hooray
It's the best place for me
For Pinkie...

Pinkie Pie: With decorations like streamers and fairy-lights and pinwheels and piÃ±atas and pin-cushions. With goodies like sugar cubes and sugar canes and sundaes and sun-beams and sarsaparilla. And I get to play my favorite-est of favorite fantabulous games like Pin the Tail on the Pony!

[Pinkie Pie]
Oh the Grand Galloping Gala is the best place for me
Oh the Grand Galloping Gala is the best place for me
'Cause it's the most galarrific superly-terrific gala ever
In the whole galaxy
Wheee!",
            'slug' => 'pinkies-gala-fantasy-song',
        ]);
        DB::table('show_songs')->insert([
            'id' => 5,
            'title' => 'The Ticket Song',
            'lyrics' => "[Pinkie Pie]
Twilight is my bestest friend
Whoopie, whoopie!

Twilight Sparkle: Pinkie...

[Pinkie Pie]
She's the cutest, smartest, all around best pony, pony!

Twilight Sparkle: Pinkie.

[Pinkie Pie]
I bet if I throw a super-duper fun party, party!

Twilight Sparkle: Pinkie!

[Pinkie Pie]
She'll give her extra ticket to the gala to me!

Twilight Sparkle: PIIINKIIIE!!",
            'slug' => 'the-ticket-song',
        ]);
        DB::table('show_songs')->insert([
            'id' => 6,
            'title' => 'Junior Speedsters Chant',
            'lyrics' => "[Rainbow Dash/Gilda]
Junior Speedsters are our lives,
Sky-bound soars and daring dives
Junior Speedsters, it's our quest,
To some day be the very best!",
            'slug' => 'junior-speedsters-chant',
        ]);
        DB::table('show_songs')->insert([
            'id' => 7,
            'title' => 'Hop Skip and Jump song',
            'lyrics' => "[Pinkie Pie]
It's not very far
Just move your little rump
You can make it if you try with a hop, skip and jump

Twilight Sparkle: We don't have time for this.

[Pinkie Pie]
A hop, skip and jump,
Just move your little rump,
A hop, skip and jump,
A hop, skip and jump,
A hop, skip and jump,
A hop skip and jump,
A hop skip and jump!",
            'slug' => 'hop-skip-and-jump-song',
        ]);
        DB::table('show_songs')->insert([
            'id' => 8,
            'title' => 'Evil Enchantress song',
            'lyrics' => "[Pinkie Pie/Flutterguy]
She's an evil enchantress
She does evil dances
And if you look deep in her eyes
She'll put you in trances
Then what will she do?
She'll mix up an evil brew
Then she'll gobble you up
In a big tasty stew
Soooo.... Watch out!",
            'slug' => 'evil-enchantress-song',
        ]);
        DB::table('show_songs')->insert([
            'id' => 9,
            'title' => 'Winter Wrap Up',
            'lyrics' => "[Rainbow Dash]
Three months of winter coolness
And awesome holidays

[Pinkie Pie]
We've kept our hoovsies warm at home
Time off from work to play

[Applejack]
But the food we've stored is runnin' out
And we can't grow in this cold

[Rarity]
And even though I love my boots
This fashion's getting old

[Twilight Sparkle]
The time has come to welcome spring
And all things warm and green
But it's also time to say goodbye
It's winter we must clean
How can I help? I'm new, you see
What does everypony do?
How do I fit in without magic?
I haven't got a clue!

[Choir]
Winter Wrap Up! Winter Wrap Up!
Let's finish our holiday cheer
Winter Wrap Up! Winter Wrap Up!

[Applejack]
'Cause tomorrow springâ€“

[Rainbow Dash]
â€“is here!

[Choir]
'Cause tomorrow spring is here!

[Rainbow Dash]
Bringing home the southern birds
A Pegasus' job begins
And clearing all the gloomy skies
To let the sunshine in
We move the clouds
And we melt the white snow

[Rainbow Dash and Pinkie Pie]
When the sun comes up
Its warmth and beauty will glow!

[Choir]
Winter Wrap Up! Winter Wrap Up!
Let's finish our holiday cheer
Winter Wrap Up! Winter Wrap Up!
'Cause tomorrow spring is here
Winter Wrap Up! Winter Wrap Up!
'Cause tomorrow spring is here
'Cause tomorrow spring is here!

[Rarity]
Little critters hibernate
Under the snow and ice

[Fluttershy]
We wake up all their sleepy heads
So quietly and nice

[Rarity]
We help them gather up their food
Fix their homes below

[Fluttershy]
We welcome back the southern birds

[Fluttershy and Rarity]
So their families can grow!

[Choir]
Winter Wrap Up! Winter Wrap Up! ([Rarity] Winter, winter)
Let's finish our holiday cheer
Winter Wrap Up! Winter Wrap Up! ([Rarity] Winter, winter)
'Cause tomorrow spring is here
Winter Wrap Up! Winter Wrap Up! ([Rarity] Winter, winter)
'Cause tomorrow spring is here
'Cause tomorrow spring is here!

[Applejack]
No easy task to clear the ground
Plant our tiny seeds
With proper care and sunshine
Everyone it feeds
Apples, carrots, celery stalks
Colorful flowers too
We must work so very hard

[Applejack, Cherry Berry, and Golden Harvest]
It's just so much to do!

[Choir]
Winter Wrap Up! Winter Wrap Up!
Let's finish our holiday cheer
Winter Wrap Up! Winter Wrap Up!
'Cause tomorrow spring is here
Winter Wrap Up! Winter Wrap Up!

[Pinkie Pie]
'Cause tomorrow spring is here

[Choir]
'Cause tomorrow spring is here!

[Twilight Sparkle]
Now that I know what they all do
I have to find my place
And help with all of my heart
Tough task ahead I face
How will I do without my magic
Help the Earth pony way
I wanna belong so I must
Do my best today,
Do my best today!

[Choir]
Winter Wrap Up! Winter Wrap Up!
Let's finish our holiday cheer
Winter Wrap Up! Winter Wrap Up!
'Cause tomorrow spring is here
Winter Wrap Up! Winter Wrap Up!

[Twilight Sparkle]
'Cause tomorrow spring is here
'Cause tomorrow spring is here
'Cause tomorrow spring is here!",
            'slug' => 'winter-wrap-up',
        ]);
        DB::table('show_songs')->insert([
            'id' => 10,
            'title' => 'Cupcake Song',
            'lyrics' => "[Pinkie Pie]
All you have to do is take a cup of flour!
Add it to the mix!
Now just take a little something sweet, not sour!
A bit of salt, just a pinch!

Baking these treats is such a cinch!
Add a teaspoon of vanilla!
Add a little more, and you count to four,
And you never get your fill of...

Cupcakes! So sweet and tasty!
Cupcakes! Don't be too hasty!
Cupcakes! Cupcakes, cupcakes, CUPCAKES!",
            'slug' => 'cupcake-song',
        ]);
        DB::table('show_songs')->insert([
            'id' => 11,
            'title' => 'Art of the Dress',
            'lyrics' => "[Rarity]
Thread by thread, stitching it together
Twilight's dress, cutting out the pattern snip by snip
Making sure the fabric folds nicely
It's the perfect color and so hip
Always gotta keep in mind my pacing
Making sure the clothes' correctly facing
I'm stitching Twilight's dress

Yard by yard, fussing on the details
Jewel neckline, don't you know a stitch in time saves nine?
Make her something perfect to inspire
Even though she hates formal attire
Gotta mind those intimate details
Even though she's more concerned with sales
It's Applejack's new dress

Dressmaking's easy, for Pinkie Pie something pink
Fluttershy something breezy
Blend color and form,

[To Opalescence] Do you think it looks cheesy?

Something brash, perhaps quite fetching
Hook and eye, couldn't you just simply die?
Making sure it fits forelock and crest
Don't forget some magic in the dress
Even though it rides high on the flank
Rainbow won't look like a tank
I'm stitching Rainbow's dress

Piece by piece, snip by snip
Croup, dock, haunch, shoulders, hip
Thread by thread, primmed and pressed
Yard by yard, never stressed
And that's the art of the dress!",
            'slug' => 'art-of-the-dress',
        ]);
        DB::table('show_songs')->insert([
            'id' => 12,
            'title' => 'Hush Now Lullaby',
            'lyrics' => "[Fluttershy]
Hush now, quiet now
It's time to lay your sleepy head
Hush now, quiet now
It's time to go to bed
Hush Now Lullaby (Sweetie Belle)

[Sweetie Belle]
Hush now! Quiet now!
It's time to lay your sleepy head!
Said hush now! Quiet now!
It's time to go to bed!

Fluttershy: Okay Sweetie, that was...

[Sweetie Belle]
Driftin' (driftin') off to sleep!
The exciting day behind you!
Driftin' (driftin') off to sleep!
Let the joy of dream land find you!

Fluttershy: Thank you Sweetie, um...

[Sweetie Belle]
Hush now! Quiet now!
Lay your sleepy head!
Said hush now! Quiet now!
It's time to go to BED!

OW!",
            'slug' => 'hush-now-lullaby',
        ]);
        DB::table('show_songs')->insert([
            'id' => 13,
            'title' => 'Cutie Mark Crusaders Song',
            'lyrics' => "[Scootaloo]
Look, here, are three little ponies
Ready to sing for this crowd
Listen up, 'cause here's our story
I'm gonna sing it

[Sweetie Belle, Apple Bloom, and Scootaloo]
Very loud!

[Scootaloo]
When you're a younger pony
And your flank is very bare
Feels like the sun will never come
When your cutie mark's not there

So the three of us will fight the fight
There is nothing that we fear
We'll have to figure out what we'll do next

[Sweetie Belle, Apple Bloom, and Scootaloo]
Till our cutie marks are here!

We are the Cutie Mark Crusaders
On a quest to find out who we are
And we will never stop the journey
Not until we have our cutie marks

[Scootaloo]
They all say that you'll get your mark
When the time is really right
And you know just what you're supposed to do
And your talent comes to light

But it's not as easy as it sounds
And that waiting's hard to do
So we test our talents everywhere

[Sweetie Belle, Apple Bloom, and Scootaloo]
Until our face is blue

We are the Cutie Mark Crusaders
On a quest to find out who we are
And we will never stop the journey
Not until we have our cutie marks

We are the Cutie Mark Crusaders
On a quest to find out who we are
And we will never stop the journey
Not until we have our cutie marks!",
            'slug' => 'cutie-mark-crusaders-song',
        ]);
        DB::table('show_songs')->insert([
            'id' => 14,
            'title' => 'You Got to Share, You Got to Care',
            'lyrics' => "[Pinkie Pie]
We may be divided
But of you all, I beg
To remember we're all hoofed
At the end of each leg

No matter what the issue
Come from wherever you please
All this fighting gets you nothing
But hoof and mouth disease

Arguing's not the way
Hey, come out and play!
It's a shiny, new day
So, what do you say?

You gotta share
You gotta care
It's the right thing to do
You gotta share
You gotta care
And there'll always be a way through

Both our diets, I should mention
Are completely vegetarian
We all eat hay and oats
Why be at each other's throat?

You gotta share
You gotta care
It's the right thing to do
And there'll always be a way
Thro-o-o-o-ugh!",
            'slug' => 'you-got-to-share-you-got-to-care',
        ]);
        DB::table('show_songs')->insert([
            'id' => 15,
            'title' => 'So Many Wonders',
            'lyrics' => "[Fluttershy]
What is this place
filled with so many wonders?
Casting its spell
That I am now under

Squirrels in the trees
and the cute little bunnies
Birds flying free
and bees with their honey

Hooneeeeey!

Oooh, what a magical place
and I owe it all to the Pegasus race
If I knew the ground had so much up its sleeve
I'd have come here sooner, and never leave

Yes, I love everythiiiiiiiiiiiing!",
            'slug' => 'so-many-wonders',
        ]);
        DB::table('show_songs')->insert([
            'id' => 16,
            'title' => "Pinkie Pie's Singing Telegram",
            'lyrics' => "[Pinkie Pie]
This is your singing telegram
I hope it finds you well
You're invited to a party
'Cause we think you're really swell

Gummy's turning one year old
So help us celebrate
The cake will be delicious
The festivities first-rate

There will be games and dancing
Bob for apples, cut a rug
And when the party's over
We'll gather 'round for a group hug

No need to bring a gift
Being there will be enough
Birthdays mean having fun with friends
Not getting lots of stuff

It won't be the same without you
So we hope that you say yes
So, please, oh please R.S.V.P
And come, and be our guest!",
            'slug' => 'pinkie-pies-singing-telegram',
        ]);
        DB::table('show_songs')->insert([
            'id' => 17,
            'title' => 'At the Gala',
            'lyrics' => "Twilight Sparkle: I can't believe we're finally here. With all that we've imagined, the reality of this night is sure to make this... The Best Night Ever!

At the Gala

[Choir]
At the Gala

[Fluttershy]
At the Gala, in the garden
I'm going to see them all!
All the creatures, I'll befriend them at the Gala! (at the Gala!)
All the birdies, and the critters
They will love me big and small!
We'll become good friends forever
Right here at the Gala!

[Choir]
All our dreams will come true right here at the Gala, at the Gala!

[Applejack]
At the Gala (it's amazing!), I will sell them (better hurry!)
All my appletastic treats! (yummy, yummy!)
Hungry ponies (they'll be snacking!), they will buy them (bring your money!)
Caramel apples, apple sweets! (gimme some!)
And I'll earn a lot of money for the Apple family!

[Choir]
All our dreams and our hopes from now until hereafter
All that we've been wishing for will happen at the Gala, at the Gala!

[Rarity]
At the Gala, all the royals
They will meet fair Rarity
They will see I'm just as regal at the Gala! (at the Gala)
I will find him, my Prince Charming,
And how gallant he will be,
He will treat me like a lady, tonight at the Gala!

[Choir]
This is what we've waited for, to have the best night ever!
Each of us will live our dreams, tonight at the Gala, at the Gala!
[Rainbow Dash]
Been dreaming, I've been waiting
To fly with those brave ponies
The Wonderbolts, their daring tricks
Spinning 'round and having kicks
Perform for crowds of thousands
They'll shower us with diamonds
The Wonderbolts will see me right here at the Gala!

[Choir]
All we've longed for, all we've dreamed, our happy ever after!
Finally will all come true, right here at the Grand Gala, at the Gala!

[Pinkie Pie]
I am here at the Grand Gala, for it is the best party
But the one thing it was missing was a pony named Pinkie
For I am the best at parties, all the ponies will agree
Ponies playing, ponies dancing, with me at the Grand Gala!

[Choir]
Happiness and laughter at the Gala, at the Gala!

[Twilight Sparkle]
At the Gala (at the Gala), with the Princess (with the Princess)
Is where I'm going to be (she will be)
We will talk all about magic and what I've learned and seen (she will see)
It is going to be so special as she takes time just for me!

[Choir]
This will be the best night ever!
Into the Gala we must go, we're ready now, we're all aglow
Into the Gala, let's go in and have the best night ever
Into the Gala, now's the time, we're ready and we look divine

[Choir + Fluttershy]
Into the Gala

[Fluttershy]
Meet new friends

[Choir + Applejack]
Into the Gala

[Applejack]
Sell some apples

[Choir + Rarity]
Into the Gala

[Rarity]
Find my prince

[Choir + Rainbow Dash]
Prove I'm great

[Rainbow Dash]
As a Wonderbolt is

Fluttershy: To meet!
Applejack: To sell!
Rarity: To find!
Rainbow Dash: To prove!
Pinkie Pie: To whoo!
Twilight Sparkle: To talk!

[All]
Into the Gala, into the Gala!
And we'll have the best night ever!
At the Gala!",
            'slug' => 'at-the-gala',
        ]);
        DB::table('show_songs')->insert([
            'id' => 18,
            'title' => "I'm at the Grand Galloping Gala",
            'lyrics' => "[Pinkie Pie]
I'm at the Grand Galloping Gala,
I'm at the Grand Galloping Gala,
I'm at the Grand Galloping Gala,
It's all I ever dreamed.

It's all I ever dreamed, woo hoo!
It's all I ever dreamed, yippee!
I'm at the Grand Galloping GalaaaaaaaaaaAAAAAAAAAAAA!
[pause]

It's all I ever... dreamed?",
            'slug' => 'im-at-the-grand-galloping-gala',
        ]);
        DB::table('show_songs')->insert([
            'id' => 19,
            'title' => 'Pony Pokey',
            'lyrics' => "[Pinkie]
You reach your right hoof in
You reach your right hoof out
You reach your right hoof in
And you shake it all about
You do the Pony Pokey meeting lots of folks with clout
That's what I'm talking about

You step your left hoof in
You pull it right back out
You step your left hoof in
But you better help him out
You do the Pony Pokey but should find a different route
That's what it's all about

You kick your back left in
You pull your back left out
You reach your back left in
Just be brave and have no doubt
You do the Pony Pokey feeling like you're gonna pout
That's what I'm singing about

You tilt your head in
You tilt your head out
You tilt your head in
Then you shake it all about
You do the Pony Pokey even though your date's a lout
You're better off without

You stomp your whole self in
You stomp your whole self out
You stomp your whole self in
And you stomp yourself about
You do the Pony Pokey and you give a little shout-

Fluttershy: COME OUT!

[Pinkie Pie]
That's what I'm talking about
You do the Pony Pokey
You do the Pony Pokey
You do the Pony Pokey
And that's what it's all about

Yeah!",
            'slug' => 'pony-pokey',
        ]);
        DB::table('show_songs')->insert([
            'id' => 20,
            'title' => 'Find A Pet Song',
            'lyrics' => "[Fluttershy]
Now, Rainbow, my dear, I cannot express my delight
It's abundantly clear
That somewhere out here
Is the pet that will suit you just right

[Rainbow Dash]
I can't wait to get started, but first let me set a few rules
It's of utmost importance
The pet that I get
Is something that's awesome and cool

Fluttershy: Awesome, cool, got it!
I have so many wonderful choices, just wait, you will see

[Rainbow Dash]
I need something real fast like a bullet to keep up with me

[Fluttershy]
Sure! How 'bout a bunny?
They're cutesy and wootsie and quick as can be

[Rainbow Dash]
Cutesy, wootsie? Have you even met me?

[Fluttershy]
Rainbow, have faith
You see, I will bet you
Somewhere in here is the pet that will get you

Fluttershy: Come on, the sky's the limit!
Rainbow Dash: Sky is good. I'd like it to fly.
Fluttershy: Really? Because I think this widdle puddy tat has your name written all over it. Yes, he does. Aww, look, he likes you!
Rainbow Dash: Pass.

[Fluttershy]
I have so many wonderful choices for you to decide
There are otters and seals
With massive appeal

Rainbow Dash: Otters and seals do not fly.
Fluttershy: Maybe not, but I've seen this particular seal catch ten feet of air when he breaches the water!
Rainbow Dash: That's it. I'm outta here.

[Fluttershy]
Wait! There must be a pet here
That will fit the ticket
How 'bout a ladybug, or a cute cricket?

Rainbow Dash: Bigger. And cooler.
Fluttershy: Bigger, cooler. Right.

[Fluttershy]
I've got just the thing in that tree, Dash
Meet your new fabulous pet, Squirrely

Rainbow Dash: It's just a squirrel.
Fluttershy: Not just any squirrel. A flying squirrel!
Rainbow Dash: ...Yeah. So, like I was saying...

[Rainbow Dash]
Fluttershy, pal, this won't cut it
I need a pet to keep up with me
Something awesome, something flying
With coolness that defies gravity!

Fluttershy: I'm sensing you want an animal that can fly.
Rainbow Dash: Ya think?

[Fluttershy]
I have plenty of wonderful creatures who soar in the sky
Like a sweet hummingbird or a giant monarch butterfly

Rainbow Dash: Better, but cooler.

[Fluttershy]
I see. How 'bout an owl, or a wasp, or a toucan?
There's so many wonderful creatures the likes of that
There are falcons and eagles
They are both quite regal
Or perhaps what you need is a dark and mysterious bat?

Rainbow Dash: Now you're talking. But instead of just one standout, now that's too many.

[Rainbow Dash]
So many choices, and such riches aplenty

Fluttershy: Not a bad problem to have, if you ask me.

[Rainbow Dash]
The bat would be awesome, but the wasp I'm digging too
Do you have something in a yellow striped bat?

Fluttershy: No.

[Fluttershy]
I've got a hot pink flamingo, just dying to meet you

[Rainbow Dash]
What to do, what to do? [gasp]
A prize! That's it! There's really just one way
To find out which animal's best
Hold a contest of speed, agility, and guts
That will put each pet to the test

[Fluttershy]
Don't forget style, that should be considered

[Rainbow Dash]
Then we'll know for sure who's best of the litter

[Fluttershy]
The one who is awesome as cool

[Rainbow Dash]
Just like me
Can't settle for less, 'cause I'm the best

[Fluttershy and Rainbow Dash]
So a contest we will see

[Rainbow Dash]
Who's the number one, greatest, perfectest pet

[Fluttershy and Rainbow Dash]
In the world for me

[Fluttershy]
May the games

[Fluttershy and Rainbow Dash]
Begin

Rainbow Dash: And may the best pet win!",
            'slug' => 'find-a-pet-song',
        ]);
        DB::table('show_songs')->insert([
            'id' => 21,
            'title' => 'Becoming Popular (The Pony Everypony Should Know)',
            'lyrics' => "[Rarity]
I'll be the toast of the town, the girl on the go
I'm the type of pony everypony, everypony should know

I'll be the one to watch, the girl in the flow
I'm the type of pony everypony, everypony should know
Becoming as popular as popular can be
Making my mark, making my mark in high society
I'm the belle of the ball, the star of the show, yeah
I'm the type of pony everypony, everypony should know

See how they hang on every word that I speak
My approving glance is what they all seek
I'm the crÃ¨me de la crÃ¨me, not just another Jane Doe
I'm the type of pony everypony should know

At home, at the opera, on a fancy yacht
Becoming the talk, the talk of all of Canterlot
I'm the crÃ¨me de la crÃ¨me, not just another Jane Doe, yeah
I'm the type of pony everypony, everypony should know

Because I'm the type of pony
Yes, I'm the type of pony
Yes, I'm the type of pony everypony should know",
            'slug' => 'becoming-popular-the-pony-everypony-should-know',
        ]);
        DB::table('show_songs')->insert([
            'id' => 22,
            'title' => 'The Heart Carol',
            'lyrics' => "[Choir]
The fire of friendship lives in our hearts
As long as it burns we cannot drift apart
Though quarrels arise, their numbers are few
Laughter and singing will see us through (will see us through)
We are a circle of pony friends
A circle of friends we'll be to the very end",
            'slug' => 'the-heart-carol',
        ]);
        DB::table('show_songs')->insert([
            'id' => 23,
            'title' => 'Happy Monthiversary',
            'lyrics' => "[Pinkie Pie]
Happy monthiversary to you and you today
[very quickly] I can't believe you're already a month old time sure flies doesn't it well it seems like only yesterday you were born.
But now you're a month old today, hey!",
            'slug' => 'happy-monthiversary',
        ]);
        DB::table('show_songs')->insert([
            'id' => 24,
            'title' => 'Piggy Dance',
            'lyrics' => '[Pinkie Pie]
First you jiggle your tail! Oink oink oink!
Then you wriggle your snout! Oink oink oink!
Then you wiggle your rump! Oink oink oink!
Then shout it out! Oink oink oink!
[repeat verse two more times]',
            'slug' => 'piggy-dance',
        ]);
        DB::table('show_songs')->insert([
            'id' => 25,
            'title' => 'The Flim Flam Brothers',
            'lyrics' => "[Flim]
Well, lookie what we got here, brother of mine, it's the same in every town
Ponies with thirsty throats, dry tongues, and not a drop of cider to be found
Maybe they're not aware that there's really no need for this teary despair

[Flam]
That the key that they need to solve this sad cider shortage you and I will share

Ponies: [excited chattering]

[Flim and Flam]
Well you've got opportunity
In this very community

[Flam]
He's Flim

[Flim]
He's Flam

[Flim and Flam]
We're the world famous Flim Flam brothers
Traveling salesponies nonpareil

Pinkie Pie: Non-pa what?

[Flim]
Nonpareil, and that's exactly the reason why, you see
No pony else in this whole place will give you such a chance to be where you need to be
And that's a new world, with tons of cider
Fresh squeezed and ready for drinking

[Flam]
More cider than you can drink in all your days of thinking.

Rainbow Dash: I doubt that.

[Flim and Flam]
So take this opportunity

[Flim, Flam, and Crowd]
In this very community

[Flam]
He's Flim

[Flim]
He's Flam

[Flim and Flam]

We're the world famous Flim Flam brothers
Traveling salesponies nonpareil

[Flim]
I suppose by now you're wondering 'bout our peculiar mode of transport

[Flam]
I say, our mode of locomotion

[Flim]
And I suppose by now you're wondering, where is this promised cider?

[Flam]
Any horse can make a claim and any pony can do the same

[Flim]
But my brother and I have something most unique and superb
Unseen at any time in this big new world

[Flim and Flam]
And that's opportunity

[Flim]
Folks, it's the one and only, the biggest and the best

[Flam]
The unbelievable

[Flim]
Unimpeachable

[Flam]
Indispensable

[Flim]
I can't believe-able

[Flim and Flam]
Flim Flam brothers' Super Speedy Cider Squeezy 6000

Flam: What d'you say, sister?

[Crowd]
Oh, we got opportunity
In this very community
Please Flim, please Flam, help us out of this jam
With your Flim Flam brothers' Super Speedy Cider Squeezy 6000

Flim: Young filly, I would be ever so honored if you might see fit to let my brother and I borrow some of your delicious, and might I add spell-bindingly fragrant apples for our little demonstration here?

Applejack: Uh, sure, I guess.

[Crowd]
Opportunity, in our community

[Flam]
Ready Flim?

[Flim]
Ready Flam?

[Flim and Flam]
Let's bing-bang zam!

Flim: And show these thirsty ponies a world of delectable cider!

[Crowd]
Cider, cider, cider, cider... [continues until Granny Smith interrupts]

Flim: Watch closely my friends!
Flam: The fun begins!
Flim: Now, here's where the magic happens, right here in this heaving roiling cider press boiling guts of the very machine, those apples plucked fresh are right now as we speak being turned into grade-A top-notch five-star blow-your-horseshoes-off one-of-a-kind cider!
Flam: Feel free to take a sneak peek!

[Granny Smith]
Now wait, you fellers, hold it!
You went and over-sold it!
I guarantee that what you have there won't compare
For the very most important ingredient
Can't be added or done expedient
And it's quality, friends, Apple Acre's quality and care!

[Flim]
Well Granny, I'm glad you brought that up, my dear, I say I'm glad you brought that up
You see that we are very picky when it comes to cider if you'll kindly try a cup

[Flam]
Yes, sir, yes ma'am this great machine lets just the very best
So whaddaya say then, Apples
Care to step into the modern world
And put the Super Speedy Cider Squeezy 6000 to the test?

[Crowd]
Cider, cider, cider, cider... [continues until Flim and Flam begin singing]

Flim: What do you think, folks? Do you see what the Apples can't? I see it clear as day! I know she does! So does he! C'mon Ponyville, you know what I'm talking about!

[Flim and Flam]
We're saying you've got

[Flim, Flam, and Crowd]
Opportunity
In this very community
He's Flim, he's Flam
We're the world famous Flim Flam brothers
Traveling salesponies nonpareil

[Flim and Flam]
Yeah!",
            'slug' => 'the-flim-flam-brothers',
        ]);
        DB::table('show_songs')->insert([
            'id' => 26,
            'title' => 'The Perfect Stallion',
            'lyrics' => "[Sweetie Belle]
Cheerilee is sweet and kind.
She's the best teacher we could hope for.
The perfect stallion you and I must find.
One to really make her heart soar.

But...
This one's too young.
This one's too old.
He clearly has a terrible cold.

Hay Fever: Achoo!

[Apple Bloom]
This guy's too silly.
He's way too uptight.

Persnickety: I say!

[Sweetie Belle]
Well nothing's wrong with this one.
He seems alright.

Scootaloo: His girlfriend sure thinks so.

[Sweetie Belle]
How 'bout this one?

[Apple Bloom]
He's much too flashy.

[Scootaloo]
He might do,

[Apple Bloom and Sweetie Belle]
If he weren't so splashy.

[Apple Bloom]
Too short.

[Sweetie Belle]
Too tall.

[Apple Bloom]
Too clean.

[Scootaloo]
Too smelly.

[Sweetie Belle]
Too strangely obsessed with tubs of jelly.

Apple Bloom, Scootaloo, and Sweetie Belle: [sigh]

[Apple Bloom]
I don't think that we're mistaken.
It seems all the good ones are taken.

[Sweetie Belle]
I really feel that at this rate,
We'll never find the perfect date.

[Apple Bloom, Scootaloo, and Sweetie Belle]
Don't wanna quit and give up hope.

Scootaloo: Doing anything special for Hearts and Hooves Day?

[Sweetie Belle]
Oh please, oh please oh please say-

Big McIntosh: Nope.
Apple Bloom, Scootaloo, and Sweetie Belle: [gasp]

[Sweetie Belle]
We did it girls. We've found the one.
Who will send our teacher's heart aflutter.

Apple Bloom: Wait a minute. Let me get this straight. Are you talking about my brother?",
            'slug' => 'the-perfect-stallion',
        ]);
        DB::table('show_songs')->insert([
            'id' => 27,
            'title' => 'Smile Song',
            'lyrics' => "[Pinkie Pie]
My name is Pinkie Pie (Hello!)
And I am here to say (How ya doin'?)
I'm gonna make you smile, and I will brighten up your day-aaay!
It doesn't matter now (What's up?)
If you are sad or blue (Howdy!)
'Cause cheering up my friends is just what Pinkie's here to do

'Cause I love to make you smile, smile, smile
Yes I do
It fills my heart with sunshine all the while
Yes it does
'Cause all I really need's a smile, smile, smile
From these happy friends of mine

I like to see you grin (Awesome!)
I would love to see you beam (Rock on!)
The corners of your mouth turned up
Is always Pinkie's dream (Hoof-bump!)
But if you're kind of worried
And your face has made a frown
I'll work real hard and do my best
To turn that sad frown upside down

'Cause I love to make you grin, grin, grin
Yes I do
Bust it out from ear to ear, let it begin
Just give me a joyful grin, grin, grin
And you fill me with good cheer

It's true, some days are dark and lonely
And maybe you feel sad
But Pinkie will be there to show you that it isn't that bad
There's one thing that makes me happy
And makes my whole life worthwhile
And that's when I talk to my friends and get them to smile

I really am so happy
Your smile fills me with glee
I give a smile, I get a smile
And that's so special to me

'Cause I love to see you beam, beam, beam
Yes I do
Tell me, what more can I say to make you see
That I do
It makes me happy when you beam, beam, beam
Yes, it always makes my day!

Come on everypony smile, smile, smile
Fill my heart up with sunshine, sunshine
All I really need's a smile, smile, smile
From these happy friends of mine!

[Choir and Pinkie Pie]
Come on everypony smile, smile, smile
Fill my heart up with sunshine, sunshine
All I really need's a smile, smile, smile
From these happy friends of mine!

[Pinkie Pie]
Yes a perfect gift for me ([Choir] Come on everypony smile, smile, smile)
Is a smile as wide as a mile (Fill my heart up with sunshine, sunshine)
To make me happy as can be (All I really need's a smile, smile, smile; from these happy friends of...)

[Choir and Pinkie Pie]
Smile, smile, smile, smile, smile!

[Pinkie Pie]
Come on and smile
Come on and smile!",
            'slug' => 'smile-song',
        ]);
        DB::table('show_songs')->insert([
            'id' => 28,
            'title' => 'Cranky Doodle Donkey',
            'lyrics' => "[Pinkie Pie]
You're a Cranky Doodle Donkey guy.
A Cranky Doodle Donkey.
I never met you but you're my new friend and I'm your best friend Pinkie Pie!",
            'slug' => 'cranky-doodle-donkey',
        ]);
        DB::table('show_songs')->insert([
            'id' => 29,
            'title' => 'Welcome Song',
            'lyrics' => '[Pinkie Pie]
Welcome welcome welcome
A fine welcome to you
Welcome welcome welcome
I say how do you do?
Welcome welcome welcome
I say hip hip hurray
Welcome welcome welcome
To Ponyville today

Pinkie Pie: Wait for it!',
            'slug' => 'welcome-song',
        ]);
        DB::table('show_songs')->insert([
            'id' => 30,
            'title' => 'Cranky Doodle Joy',
            'lyrics' => "[Pinkie Pie]
He had a Cranky Doodle sweetheart
She's his cranky doodle joy
I helped the Cranky Doodle boy, yeah!
I helped the Cranky Doodle boy!

Cranky Doodle Donkey and Matilda: Pinkie!
Pinkie Pie: Whoops, privacy. Sorry.",
            'slug' => 'cranky-doodle-joy',
        ]);
        DB::table('show_songs')->insert([
            'id' => 31,
            'title' => 'Big Brother Best Friend Forever (B.B.B.F.F.)',
            'lyrics' => "[Twilight Sparkle]
When I was just a filly, I found it rather silly
To see how many other ponies I could meet
I had my books to read, didn't know that I would ever need
Other ponies to make my life complete

But there was one colt that I cared for
I knew he would be there for me

My big brother, best friend forever!
Like two peas in a pod, we did everything together
He taught me how to fly a kite (Best friend forever!)
We never had a single fight (We did everything together!)
We shared our hopes, we shared our dreams
I miss him more than I realized
It seems...

[Rest of main six]
Your big brother, best friend forever
Like two peas in a pod, you did everything together

[Twilight Sparkle]
And though he's, oh, so far away
I hoped that he would stay
My big brother best friend
Forever...
Forever...",
            'slug' => 'big-brother-best-friend-forever-bbbff',
        ]);
        DB::table('show_songs')->insert([
            'id' => 32,
            'title' => 'This Day Aria',
            'lyrics' => "[Queen Chrysalis]
This day is going to be perfect
The kind of day of which I've dreamed since I was small
Everypony will gather 'round
Say I look lovely in my gown
What they don't know is that I have fooled them all!

[Princess Cadance]
This day was going to be perfect
The kind of day of which I've dreamed since I was small
But instead of having cake
With all my friends to celebrate
My wedding bells, they may not ring for me at all

[Queen Chrysalis]
I could care less about the dress
I won't partake in any cake
Vows, well I'll be lying when I say
That through any kind of weather
I'll want us to be together
The truth is I don't care for him at all
No I do not love the groom
In my heart there is no room
But I still want him to be all mine

[Princess Cadance]
Must escape before it's too late
Find a way to save the day
Hope, I'll be lying if I say
\"I don't fear that I may lose him
To one who wants to use him
Not care for, love and cherish him each day\"
For I oh-so love the groom
All my thoughts he does consume
Oh Shining Armor, I'll be there very soon

[Queen Chrysalis]
Finally the moment has arrived
For me to be one lucky bride

[Princess Cadance]
Oh, the wedding we won't make
He'll end up marrying a fake
Shining Armor will be

[Queen Chrysalis]: ...mine, all mine. [evil laugh]",
            'slug' => 'this-day-aria',
        ]);
        DB::table('show_songs')->insert([
            'id' => 33,
            'title' => 'Love Is In Bloom',
            'lyrics' => "[Twilight Sparkle]
Love is in bloom
A beautiful bride, a handsome groom,
Two hearts becoming one
A bond that cannot be undone because

Love is in bloom
A beautiful bride, a handsome groom
I said love is in bloom
You're starting a life and making room
For us (For us, For us...)

Your special day
We celebrate now, the pony way
Your friends are all right here
Won't let these moments disappear because

Love is in bloom
A beautiful bride, a handsome groom
I said love is in bloom
You're starting a life and making room
For us, (For us... For us...Aah...)",
            'slug' => 'love-is-in-bloom',
        ]);
        DB::table('show_songs')->insert([
            'id' => 34,
            'title' => 'The Failure Song',
            'lyrics' => "[Twilight Sparkle]
I was prepared to do my best
Thought I could handle any test
For I can do so many tricks
But I wasn't prepared for this

						  Levitation would have been a breeze
Facts and figures I recite with ease

Twilight Sparkle: The square root of five hundred and forty-six is twenty-three point three six six six four two eight nine one zero nine.
		Teacher: She is correct!

		[Twilight Sparkle]
I could ace a quiz on friendship's bliss
But I wasn't prepared for this
						  Will I fail, or will I pass?
			I can't be sure...

[Spike]
She can't be sure...

[Twilight Sparkle]
My mind is sharp, my skills intact
My heart is pure...

[Spike]
Her heart is pure...

[Twilight Sparkle]
Oh, I've taken my share of licks
I've made it through the thin and thick
But no I wasn't

[Spike]
Oh no, she wasn't

[Twilight Sparkle]
Oh no, I wasn't

[Spike]
Oh no, she wasn't

[Twilight Sparkle]
No I wasn't

[Twilight Sparkle and Spike]
Prepared... for this!",
            'slug' => 'the-failure-song',
        ]);
        DB::table('show_songs')->insert([
            'id' => 35,
            'title' => 'The Ballad of the Crystal Empire',
            'lyrics' => '[Twilight Sparkle]
Princess Cadence needs our help
Her magic will not last forever
I think we can do it
But we need to work together
We have to get this right
Yes we have to make them see
We can save the Crystal Ponies with their history

[Rainbow Dash]
It says that they liked jousting

[Rarity]
They flew a flag of many hues

[Applejack]
Made sweets of crystal berries

[Fluttershy]
They had a petting zoo with tiny ewes

[Twilight Sparkle, Rainbow Dash, Pinkie Pie, Applejack, Rarity, Fluttershy and Spike]
Oh, we have to get this right
Yes we have to make them see
We can save the Crystal Ponies with their history

[Pinkie Pie]
There was a crystal flugelhorn
That everypony liked to play

[Twilight Sparkle]
And the Crystal Kingdom anthem
Can you learn it in a day?

[Twilight Sparkle, Rainbow Dash, Pinkie Pie, Applejack, Rarity, Fluttershy and Spike]
Oh, we have to get this right
Yes we have to make them see
We can save the Crystal Ponies... with their history!',
            'slug' => 'the-ballad-of-the-crystal-empire',
        ]);
        DB::table('show_songs')->insert([
            'id' => 36,
            'title' => 'The Success Song',
            'lyrics' => '[Rarity]
You were prepared to do your best
Had what it takes to pass the test
All those doubts you can dismiss
Turns out you were

[Applejack, Rainbow Dash, Rarity, Pinkie Pie, Fluttershy, and Spike]
Prepared for this!

[Applejack]
You clearly have just what it takes

[Pinkie Pie]
To pass a test with such high stakes

[Fluttershy]
We knew for sure you would prevail

[Rainbow Dash]
Since when does Twilight Sparkle ever fail?

[Applejack, Rainbow Dash, Rarity, Pinkie Pie, Fluttershy, and Spike]
All those doubts that you can dismiss
Trust yourself and you cannot miss

[Applejack, Rarity, and Pinkie Pie]
Turns out you were

[Twilight Sparkle]
Turns out I was

[Rainbow Dash, Fluttershy,and Spike]
Turns out you were

[Twilight Sparkle]
Turns out I was

[Rarity]
Turns out you were

[Spike, Applejack, Rainbow Dash, Rarity, Pinkie Pie, Fluttershy, and Twilight Sparkle]
Prepared... for this!',
            'slug' => 'the-success-song',
        ]);
        DB::table('show_songs')->insert([
            'id' => 37,
            'title' => 'Babs Seed',
            'lyrics' => "[Cutie Mark Crusaders]
Yeah, yeah, yeah
Yeah, yeah, yeah
Yeah, yeah, yeah, yeah, yeah

[Apple Bloom]
First, we thought that Babs was so really, really sweet
A new friend to have and it seemed like such a treat

[Scootaloo]
But then, we found the truth; she's just a bully from the east
She went from Babs, yeah, to a bully and a beast

[Apple Bloom]
Everywhere we turn, she's just a step ahead

[Cutie Mark Crusaders]
Babs Seed, Babs Seed, what we gonna do?
Got a bully on our tail
Gotta hide, we gotta bail
Babs Seed, Babs Seed, if she's after you
Gotta run, we gotta flee
Gotta hurry, don't you see?
Babs Seed, Babs Seed, she's just a bad, bad seed

Yeah, yeah, yeah
Yeah, yeah, yeah
Yeah, yeah, yeah, yeah, yeah

[Apple Bloom]
Hiding from a bully, we know it isn't right
But the Cutie Mark Crusaders, we aren't lookin' for a fight

[Scootaloo]
Oh, she'll go home soon, and then we'll have some peace again
But for now, we're staying out of her way 'til then

[Apple Bloom]
Everywhere we turn, she's just a step ahead

[Cutie Mark Crusaders]
Babs Seed, Babs Seed, what we gonna do?
Got a bully on our tail
Gotta hide, we gotta bail
Babs Seed, Babs Seed, if she's after you
Gotta run, we gotta flee
Gotta hurry, don't you see?

			Why so mean? Why so crude?

			Why so angry? Why so rude?
			Can't you be nice? Can't we be friends?
			Isn't it sad? Is this how it all ends?
Babs Seed, Babs Seed, she's just a bad, bad-
		Babs Seed, Babs Seed, she's just a bad, bad-
Babs Seed, Babs Seed-

[Scootaloo]
She's just a bad, bad seed",
            'slug' => 'babs-seed',
        ]);
        DB::table('show_songs')->insert([
            'id' => 38,
            'title' => 'Raise This Barn',
            'lyrics' => "[Applejack]
Yee-hoo!

		Raise this barn, raise this barn
One, two, three, four
Together, we can raise this barn
One, two, three, four

Up, up, up, go the beams
Hammer those joints, work in teams
Turn 'em round quick by the right elbow
Grab a new partner, here we go

Apple family: Yeah!
Applejack: Come on, Apple family! Let's get to it! Wee-hoo!

		[Applejack]
Raise this barn, raise this barn
One, two, three, four
Together, we can raise this barn
One, two, three, four

Finish the frame, recycling wood
Working hard, you're doing good
Turn 'em round quick by the right elbow
Grab your partner, here we go

Apple family: Yeah!
		Applejack: Whoo-whee!

		[Applejack]
Raise this barn, raise this barn
One, two, three, four
Together, we can raise this barn
One, two, three, four

Slats of wood come off the ground
Hold 'em up and nail 'em down
Turn 'em round quick by the left elbow
Grab a new partner, here we go

Apple family: Yeah!
Applejack: Come on, Apples! Get 'er done!

		[Apple Bloom]
Look at us, we're family

[Applejack]
Working together thankfully

[Apple Bloom]
We Apples, we are proud to say

[Applejack and Apple Bloom]
Stick together the pony way

[Applejack]
Bow to your partner, circle right
Get down if you're scared of heights
Forward back and twirl around
The barn's gonna be the best in town

Apple family: Yeah!
Applejack: Yee-haw! Attagirl!
Apple Bloom: Alright, let's get to it!

		[Apple family]
Raise this barn, raise this barn
One, two, three, four
Together, we can raise this barn
One, two, three, four

[Applejack]
Take your brushes, young and old
Together, paint it, bright and bold
Turn 'em round quick by the left elbow
Grab a new partner, here we go

[Apple family]
We raised this barn, we raised this barn
Yes, we did
Together we sure raised this barn
Yes, we did

Being together counts the most
We all came here from coast to coast
All we need to strive to be
Is part of the Apple family

Apple Bloom: Yeah!",
            'slug' => 'raise-this-barn',
        ]);
        DB::table('show_songs')->insert([
            'id' => 39,
            'title' => 'Morning in Ponyville',
            'lyrics' => "[Twilight Sparkle]
Morning in Ponyville shimmers
Morning in Ponyville shines
And I know for absolute certain
That everything is certainly fine

There's the Mayor en route to her office
There's the sofa clerk selling some quills

Store owner: Morning, kid!

[Twilight Sparkle]
My Ponyville is so gentle and still
Can things ever go wrong?
I don't think that they will

Morning in Ponyville shimmers
Morning in Ponyville shines
		And I know for absolute certain
					   That everything is certainly...",
            'slug' => 'morning-in-ponyville',
        ]);
        DB::table('show_songs')->insert([
            'id' => 40,
            'title' => 'What My Cutie Mark Is Telling Me',
            'lyrics' => "[Rainbow Dash]
These animals don't listen, no, not one little bit
They run around out of control and throw their hissy fits
It's up to me to stop them, 'cause plainly you can see
It's got to be my destiny, and it's what my cutie mark is telling me

[Fluttershy]
I try to keep them laughing, put a smile upon their face
But no matter what I try, it seems a bit of a disgrace
I have to entertain them, it's there for all to see
		It's got to be my destiny, and it's what my cutie mark is telling me

[Pinkie Pie]
I don't care much for pickin' fruit and plowin' fields ain't such a hoot
No matter what I try, I cannot fix this busted water chute!
		I've got so many chores to do, it's no fun being me
But it has to be my destiny, 'cause it's what my cutie mark is telling me

[Applejack]
Lookie here at what I made, I think that it's a dress
I know it doesn't look like much, I'm under some distress
Could y'all give me a hand here and help me fix this mess?
			My destiny is not pretty, but it's what my cutie mark is tellin' me

[Rarity]
I'm in love with weather patterns but the others have concerns
For I just gave them frostbite over-top of their sunburns
I have to keep on trying for everyone can see
It's got to be

[Fluttershy]
It's got to be

[Pinkie Pie]
My destiny

[Applejack]
My destiny

[Rarity, Rainbow Dash, and Fluttershy]
And it's what my cutie mark

[Pinkie Pie and Applejack]
It's what my cutie mark

[All]
Yes, it's what my cutie mark is telling me!",
            'slug' => 'what-my-cutie-mark-is-telling-me',
        ]);
        DB::table('show_songs')->insert([
            'id' => 41,
            'title' => "I've Got to Find a Way",
            'lyrics' => "[Twilight Sparkle]
I have to find a way
To make this all okay
I can't believe this small mistake
Could've caused so much heartache

Oh why, oh why

Losing promise
I don't know what to do
		Seeking answers
I fear I won't get through to you

Oh why, oh why",
            'slug' => 'ive-got-to-find-a-way',
        ]);
        DB::table('show_songs')->insert([
            'id' => 42,
            'title' => 'A True, True Friend',
            'lyrics' => "[Twilight Sparkle]
A true, true friend helps a friend in need

A friend will be there to help them see
[Twilight and Fluttershy]
A true, true friend helps a friend in need
To see the light that shines from a true, true friend

Rainbow Dash: Um, hello! Friend trapped inside, remember?

[Twilight Sparkle]
Rarity needs your help
She's trying hard doing what she can

[Fluttershy]
Would you try, just give it a chance
You might find that you'll start to understand

[Twilight and Fluttershy]
A true, true friend helps a friend in need
A friend will be there to help you see
A true, true friend helps a friend in need
To see the light that shines from a true, true friend

Rainbow Dash: Uh, what just happened?
Twilight Sparkle: There's no time to explain, but we need your help. Applejack's trying to make dresses!
Rainbow Dash: Say no more!

[Rainbow Dash]
Applejack needs your help
She's trying hard doing what she can
Would you try, just give it a chance
You might find that you'll start to understand

[Twilight, Fluttershy, and Rainbow Dash]
A true, true friend helps a friend in need
A friend will be there to help them see
A true, true friend helps a friend in need
To see the light that shines from a true, true friend

Rarity: [gasps] Oh my, what a terrible dream I had. Or, maybe I'm still having it.
		Twilight Sparkle: Rarity, Pinkie Pie is about to lose the apple farm. We need Applejack's help!
Rarity: Lose the apple farm? Well we can't let that happen, now can we?

			[Rarity]
			Pinkie Pie is in trouble
We need to get there by her side
We can try to do what we can now
For together we can be her guide

[Twilight, Fluttershy, Rainbow Dash, and Rarity]
A true, true friend helps a friend in need
A friend will be there to help them see
A true, true friend helps a friend in need
To see the light that shines from a true, true friend

[key up]

Applejack: Yee-haw! Now that's more like it, what's next?
			Twilight Sparkle: The townspeople are furious, we need the old Pinkie Pie back.
		Applejack: I'm on it, I know just the thing.

[Applejack]
The townspeople need you, they've been sad for a while
		They march around, faces frown and never seem to smile
		And if you feel like helping, we'd appreciate a loooooot
If you get up there and spread some cheer from here to Canterlooooooot!

Pinkie Pie: Come on ponies, I wanna see you smile!
Crowd: Pinkie!

[All and chorus]
A true, true friend helps a friend in need
A friend will be there to help them see
A true, true friend helps a friend in need
To see the light (to see the light)
That shines (that shines)
From a true, true friend!",
            'slug' => 'a-true-true-friend',
        ]);
        DB::table('show_songs')->insert([
            'id' => 43,
            'title' => "Celestia's Ballad",
            'lyrics' => "[Princess Celestia]
You've come such a long, long way
And I've watched you from that very first day
To see how you might grow
To see what you might do
		To see what you've been through
And all the ways you've made me proud of you

It's time now for a new change to come
You've grown up and your new life has begun
To go where you will go
To see what you will see
To find what you will be
For it's time for you to fulfill your destiny",
            'slug' => 'celestias-ballad',
        ]);
        DB::table('show_songs')->insert([
            'id' => 44,
            'title' => 'Behold, Princess Twilight Sparkle',
            'lyrics' => '[Choir]
Thou Princess Twilight cometh
Behold, behold
A Princess here before us
Behold, behold, behold

Behold, behold (behold, behold)
The Princess Twilight cometh
Behold, behold (behold, behold)
The Princess is
The Princess is here',
            'slug' => 'behold-princess-twilight-sparkle',
        ]);
        DB::table('show_songs')->insert([
            'id' => 45,
            'title' => 'Life in Equestria',
            'lyrics' => '[Twilight Sparkle]
Life in Equestria shimmers
Life in Equestria shines
And I know for absolute certain

[Main Cast]
That everything (everything)
Yes, everything
Yes, everything is certainly fine
Itâ€™s fine

Twilight Sparkle: Yes! Everythingâ€™s going to be just fine!',
            'slug' => 'life-in-equestria',
        ]);
    }

    public function down()
    {
        Schema::table('show_song_track', function ($table) {
            $table->dropForeign('show_song_track_track_id_foreign');
            $table->dropForeign('show_song_track_show_song_id_foreign');
        });

        Schema::drop('show_song_track');
        Schema::drop('show_songs');
    }
}
