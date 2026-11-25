<x-layouts.app :title="__('Datenschutz')" robots="index,follow">
    <article class="card mx-auto max-w-2xl space-y-4 p-6 text-sm leading-relaxed text-zinc-700 sm:p-8 dark:text-zinc-300">
        <h1 class="text-2xl font-medium text-zinc-900 dark:text-white">{{ __('Datenschutz') }}</h1>
        <p>{{ __('Zeitfinder speichert nur, was für eine Terminabstimmung nötig ist: Titel, Beschreibung und Termine einer Umfrage, Name und E-Mail-Adresse der Organisierenden und Teilnehmenden sowie deren Antworten.') }}</p>
        <h2 class="pt-2 text-lg font-medium text-zinc-900 dark:text-white">{{ __('Was passiert mit meiner E-Mail-Adresse?') }}</h2>
        <p>{{ __('Wenn du abstimmst, sehen weder die Organisierenden noch andere Teilnehmende deine E-Mail-Adresse. Sichtbar sind nur dein Name und deine Antworten. Technischen Zugriff auf die gespeicherten Adressen hat nur der Betreiber dieser Website.') }}</p>
        <p>{{ __('Die Adresse wird nur genutzt, um doppelte Abstimmungen zu verhindern, dir einen Link zum Ändern deiner Antwort zu schicken und dich über den festgelegten Termin zu informieren. Es gibt keine Werbung und keinen Newsletter.') }}</p>
        <p>{{ __('Sie wird gelöscht, sobald dein Eintrag oder die ganze Umfrage gelöscht wird. Ein Konto, das du selbst anlegst, bleibt davon unberührt.') }}</p>
        <h2 class="pt-2 text-lg font-medium text-zinc-900 dark:text-white">{{ __('Suchmaschinen und KI') }}</h2>
        <p>{{ __('Umfrageseiten sind nicht für Suchmaschinen oder KI-Modelle bestimmt: Sie sind als nicht indexierbar gekennzeichnet, und bekannte Suchmaschinen- und KI-Crawler werden abgewiesen.') }}</p>
        <h2 class="pt-2 text-lg font-medium text-zinc-900 dark:text-white">{{ __('Cookies und Löschen') }}</h2>
        <p>{{ __('Es werden keine externen Schriften, Skripte, Tracker oder Werbung geladen. Cookies dienen nur der Anmeldung und dazu, dass du deine eigene Antwort später ändern kannst.') }}</p>
        <p>{{ __('Organisierende können eine Umfrage samt aller Antworten jederzeit löschen.') }}</p>
    </article>
</x-layouts.app>
