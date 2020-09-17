<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Andreas Wahlin</h2>
    </div>
    <div class="card-body p-2">
        <p class="card-text">

            Using this space, I would like to give you all the opportunity to learn a bit about a person who I consider, even though we only spoke daily in the digital world, as a good friend.
            The first time I got in touch with Andreas was in the beginning of 2002. I was in the middle of creating a new version of my website, The ST Graveyard, and Andreas, who was also an
            avid Atari fanatic, asked me if I was interested in hosting his first assembler tutorial. Of course I was happy with the offer, and that is where it all started. Today, his tutorials
            are in the top 5 of most downloaded docs in the Atari scene. At that time, he didn't really had a lot of asm knowledge, but wrote the tutorials while he was learning it all.
            He was really into this thing. I used to love watching the little things he coded on the ST. I still remember vividly the Matrix fonts dropping from top to bottom, it was so cool.
        </p>
        <p class="card-text">
            Of course, after a while, we started communicating more. He was also a real game fanatic, so he started reviewing ST games as well. His reviews were always very elaborate and he gave it all
            a very philosophical touch. Andreas was really into the spiritual side of life, and into philosophy. He walked in the path of the Warrior Munk. That was the side of him which was hard for me
            to grasp, cause it was all new to me, yet very interesting. He always had a very different take on things in life. Yet on the other hand he could be so down to earth and funny.
        </p>
        <p class="card-text">
            Computers weren't the only thing we talked about. After a while, you start to know eachother. I used to love to talk about our mutual love for horror movies, especially George Romero and David Cronenberg films.
            We talked about family. And let's not forget to mention 'Clare Forlani' (yes, he had good taste in women !) I remember vividly our conversations on why we just couldn't find a 'nice' girlfriend
            (I know, it all sounds silly to you, but hey, we're guys and we all know women, he :-)) The things you talk about when you share the same age and interests ...
        </p>
        <p class="card-text">
            Andreas was a guy filled with cool ideas. I'm not only talking about his coding work, but we also all remember the silly adventures of Karate Kenneth. He is also responsible for the Jukebox you see
            at this site. He created this in a matter of hours. He was really enthousiastic about (web)coding and new technologies. In the past 2 years our interests took a bit of a different road (and our spare time),
            but he showed me his latest java(script) work and it looked mighty impressive! The Dungeon Master clone in javascript!
        </p>
        <p class="card-text">
            Even though I never met him in real life, after 4 years of chatting, you just know he was a cool, friendly bloke who had a lot to offer. It so sad I never met him in real life.
        </p>
        <p class="card-text text-center">
            <img class="w-50" src="{{ asset('images/class_al/Andreas2.jpg') }}">
        </p>
        <p class="card-text">
            Andreas Wahlin was killed on the 24th of February when getting of the tram in Gothenborg. He was attacked by two muggers and stabbed in the back. He died on the way to the hospital. He was
            killed for his laptop, a mac. He took that thing with him everywhere. I've had a lot of 'PC vs Mac' discussions with him in the past, and I used to tease him with this matter.
            He was another victim of the violent society we live in today.
        </p>
        <p class="card-text">
            Andreas, I just can't believe you are gone! I will be thinking about you for a very long time (in a non gay way (as we used to joke)). From now on, everytime I watch a Romero flick,
            you can be sure as hell you'll be on my mind.
        </p>
        <p class="card-text">
            Only the good die young ... it's such a shame, I just know you had so much more to offer. I hope one day we will meet again ... On the other side!
        </p>
        <p class="card-text">
            I would like to end this little message with a picture of Andreas taken 4 years ago. He was really into martial arts, it was his passion. I know he was proud of this picture.
        </p>
        <p class="card-text">
            You will be missed! I wish you could come back.
        </p>
        <p class="card-text">
            <strong>Maarten Martens (ST Graveyard) - March 7 2007</strong>
        </p>
        <p class="card-text text-center">
            <img class="w-50" src="{{ asset('images/class_al/Andreas.jpg') }}">
        </p>
    </div>
    <div class="card-body p-2">
        <h6>Visitor comments:</h6>

        @foreach ($comments as $comment)
            <h6 class="text-muted">{{ $comment->user_name}} - {{ date('F j, Y', $comment->timestamp) }}</h6>
            <p>{!! nl2br(stripslashes($comment->comment)) !!}</p>
        @endforeach
    </div>
</div>
