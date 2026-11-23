<x-layouts.app :title="__('Datenschutz')" robots="index,follow">
    <article class="card mx-auto max-w-2xl space-y-4 p-6 text-sm leading-relaxed text-zinc-700 sm:p-8 dark:text-zinc-300">
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ __('Datenschutz') }}</h1>
        <p>{{ __('Zeitfinder speichert nur, was für eine Terminabstimmung nötig ist: Titel, Beschreibung und Termine einer Umfrage, Name und E-Mail-Adresse der Organisierenden und Teilnehmenden sowie deren Antworten.') }}</p>
        <p>{{ __('E-Mail-Adressen sind nur für die Organisierenden einer Umfrage sichtbar. Sie werden genutzt, um doppelte Abstimmungen zu verhindern, Links zum Ändern zu verschicken und über den festgelegten Termin zu informieren.') }}</p>
        <p>{{ __('Es werden keine externen Schriften, Skripte, Tracker oder Werbung geladen. Cookies dienen nur der Anmeldung und dazu, dass du deine eigene Antwort später ändern kannst.') }}</p>
        <p>{{ __('Organisierende können eine Umfrage samt aller Antworten jederzeit löschen.') }}</p>
    </article>
</x-layouts.app>
