<x-guest-layout>
    <article class="legal-document">
        <nav aria-label="Account navigation">
            <a href="{{ route('login') }}">Log in</a>
            <a href="{{ route('register') }}">Create an account</a>
            <a href="{{ route('policy.show') }}">Privacy notice</a>
            <a href="{{ route('terms.show') }}">Terms of use</a>
        </nav>
        {!! $terms !!}
    </article>
</x-guest-layout>
