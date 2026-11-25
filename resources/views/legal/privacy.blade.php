@php
    $h2 = 'pt-3 text-lg font-medium text-zinc-900 dark:text-white';
@endphp
<x-layouts.app :title="__('Datenschutz')" robots="index,follow">
    <article class="card mx-auto max-w-2xl space-y-4 p-6 leading-relaxed text-zinc-700 sm:p-8 dark:text-zinc-300">
        <h1 class="text-2xl font-medium text-zinc-900 dark:text-white">{{ __('Datenschutz') }}</h1>

        <p>{{ __('Zeitfinder speichert nur, was für eine Terminabstimmung nötig ist. Es gibt keine Werbung, keinen Newsletter und kein Tracking.') }}</p>

        <h2 class="{{ $h2 }}">{{ __('Was passiert mit meiner E-Mail-Adresse?') }}</h2>
        <p>{{ __('Wenn du abstimmst, sehen weder die Organisierenden noch andere Teilnehmende deine E-Mail-Adresse. Sichtbar sind nur dein Name und deine Antworten. Technischen Zugriff auf die gespeicherten Adressen haben nur der Betreiber dieser Website und die unten genannten Dienstleister in seinem Auftrag.') }}</p>
        <p>{{ __('Die Adresse wird nur genutzt, um doppelte Abstimmungen zu verhindern, dir einen Link zum Ändern deiner Antwort zu schicken und dich über den festgelegten Termin zu informieren.') }}</p>

        @if ($legal->hasOperator())
            <h2 class="{{ $h2 }}">{{ __('Verantwortlich') }}</h2>
            @include('legal.operator')
        @endif

        <h2 class="{{ $h2 }}">{{ __('Welche Daten werden verarbeitet?') }}</h2>
        <ul class="list-disc space-y-2 pl-5">
            <li>{{ __('Umfragen: Titel, Beschreibung, Ort und Termine, Name und E-Mail-Adresse der organisierenden Person.') }}</li>
            <li>{{ __('Abstimmungen: Name, E-Mail-Adresse und Antworten der Teilnehmenden. Name und Antworten sind für alle sichtbar, die den Link zur Umfrage haben.') }}</li>
            <li>{{ __('Konten (freiwillig): Name, E-Mail-Adresse, Zeitpunkt der letzten Anmeldung, Passkeys sowie gegebenenfalls ein Passwort (nur als nicht umkehrbarer Hash) und ein verschlüsselter Zwei-Faktor-Schlüssel.') }}</li>
            <li>{{ __('Anmeldelinks: die E-Mail-Adresse, an die ein Link angefordert wurde.') }}</li>
            <li>{{ __('Aktivitätsprotokoll: Änderungen an Umfragen, Abstimmungen und Konten mit Zeitpunkt, IP-Adresse und gegebenenfalls Name oder E-Mail-Adresse, um Fehler und Missbrauch aufzuklären.') }}</li>
            <li>{{ __('Server-Protokolle: IP-Adresse, Zeitpunkt, aufgerufene Seite, vorherige Seite und Browserkennung, bei E-Mails außerdem die gekürzte Empfängeradresse. Fehlermeldungen des Mailservers können die vollständige Adresse enthalten.') }}</li>
        </ul>

        <h2 class="{{ $h2 }}">{{ __('Zwecke und Rechtsgrundlagen') }}</h2>
        <p>{{ __('Umfragen, Abstimmungen, Konten und die dazugehörigen E-Mails verarbeiten wir, um den gewünschten Dienst bereitzustellen (Art. 6 Abs. 1 Buchst. b DSGVO). Protokolle dienen dem sicheren Betrieb und der Abwehr von Missbrauch; das ist unser berechtigtes Interesse (Art. 6 Abs. 1 Buchst. f DSGVO).') }}</p>
        <p>{{ __('Du bist nicht verpflichtet, Daten anzugeben. Ohne Name und E-Mail-Adresse kannst du aber nicht abstimmen und keine Umfrage anlegen. Eine automatisierte Entscheidungsfindung oder Profilbildung findet nicht statt.') }}</p>

        @if ($legal->hosting_provider || $legal->processors)
            <h2 class="{{ $h2 }}">{{ __('Wer bekommt die Daten?') }}</h2>
            <p>{{ __('Wir geben keine Daten zu Werbezwecken weiter. Für den Betrieb setzen wir Dienstleister ein:') }}</p>
            @if ($legal->hosting_provider)
                <p class="break-words whitespace-pre-line">{{ __('Hosting') }}:
{{ $legal->hosting_provider }}</p>
            @endif
            @if ($legal->processors)
                <p class="break-words whitespace-pre-line">{{ $legal->processors }}</p>
            @endif
        @endif

        <h2 class="{{ $h2 }}">{{ __('Wie lange werden die Daten gespeichert?') }}</h2>
        <ul class="list-disc space-y-2 pl-5">
            <li>{{ __('Umfragen werden mit allen Namen, E-Mail-Adressen und Antworten :months Monate nach dem letzten vorgeschlagenen Termin automatisch gelöscht, frühestens :months Monate nach dem Anlegen. Organisierende können eine Umfrage oder einzelne Einträge jederzeit früher löschen.', ['months' => $months]) }}</li>
            <li>{{ __('Anmeldelinks werden spätestens zwei Tage nach der Anforderung gelöscht.') }}</li>
            <li>{{ __('Einträge im Aktivitätsprotokoll werden nach :months Monaten gelöscht.', ['months' => $months]) }}</li>
            <li>{{ __('Konten bleiben bestehen, bis sie gelöscht werden. Eine Nachricht an den Betreiber genügt.') }}</li>
            <li>{{ __('Server-Protokolle werden nur so lange aufbewahrt, wie es für den sicheren Betrieb nötig ist.') }}</li>
        </ul>

        <h2 class="{{ $h2 }}">{{ __('Cookies') }}</h2>
        <p>{{ __('Es werden nur technisch notwendige Cookies gesetzt: für die Sitzung und den Schutz der Formulare, für die Anmeldung und – bis zu einem Jahr – damit du in diesem Browser deine Antwort ändern oder deine Umfrage verwalten kannst. Externe Schriften, Skripte, Tracker oder Werbung werden nicht geladen.') }}</p>

        <h2 class="{{ $h2 }}">{{ __('Suchmaschinen und KI') }}</h2>
        <p>{{ __('Umfrageseiten sind nicht für Suchmaschinen oder KI-Modelle bestimmt: Sie sind als nicht indexierbar gekennzeichnet, und bekannte Suchmaschinen- und KI-Crawler werden abgewiesen.') }}</p>

        <h2 class="{{ $h2 }}">{{ __('Deine Rechte') }}</h2>
        <p>{{ __('Du hast das Recht auf Auskunft, Berichtigung, Löschung, Einschränkung der Verarbeitung und Datenübertragbarkeit (Art. 15 bis 20 DSGVO).') }}</p>
        <p class="font-semibold">{{ __('Widerspruchsrecht: Du kannst der Verarbeitung auf Grundlage berechtigter Interessen jederzeit aus Gründen widersprechen, die sich aus deiner besonderen Situation ergeben (Art. 21 DSGVO).') }}</p>
        <p>{{ __('Außerdem kannst du dich bei einer Datenschutz-Aufsichtsbehörde beschweren (Art. 77 DSGVO).') }}</p>
        @if ($legal->supervisory_authority)
            <p class="break-words whitespace-pre-line">{{ __('Zuständige Aufsichtsbehörde') }}:
{{ $legal->supervisory_authority }}</p>
        @endif

        @if ($legal->privacy_extra)
            <p class="break-words whitespace-pre-line">{{ $legal->privacy_extra }}</p>
        @endif
    </article>
</x-layouts.app>
