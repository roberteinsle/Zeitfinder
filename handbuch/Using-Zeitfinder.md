# Using Zeitfinder

## Creating a poll (organizer)

1. Open **Neue Umfrage** (`/neu`). No account is needed.
2. Enter a title and optionally a location and description.
3. Add date proposals. Each row is a date with an optional time window (*Von*/*Bis*):
   - Without a time the option is **all-day**.
   - With a start time an end time is required. An end before the start means the slot runs past midnight.
   - **Weiterer Tag** adds a row for the next day with the same times; **Weitere Uhrzeit am selben Tag** adds another slot on the same day.
   - Identical proposals are merged. At most 60 options per poll.
   - All times are German time (Europe/Berlin); they are stored in UTC.
4. Without an account enter your name and email. With an account your account email is used.
5. Options:
   - **„Wenn nötig“ erlauben** – offer a third answer besides Yes and No (default on).
   - **Bei jeder Antwort benachrichtigen** – get an email whenever someone votes.
6. Click **Umfrage erstellen**.

The confirmation page shows the **share link** (copy or share via the phone's share sheet) and your **admin link**. The admin link is also emailed to you. Keep it private: whoever has it can manage the poll.

Signed-in organizers can manage their polls from **Konto** without the admin link.

## Answering (participants)

1. Open the shared link. No account is needed.
2. Enter your name and email. The email is only visible to the organizer. It ensures that every address votes only once and lets you change your answer later.
3. Tap **Ja**, **Wenn nötig** or **Nein** for each option. **Alle Ja** / **Alle Nein** set every option at once. Unanswered options count as *No*.
4. Click **Antwort senden**.

You receive an email with a personal link to change your answer. The same browser remembers you, so you can also change the answer directly on the poll page.

If somebody tries to vote again with an address that has already voted, the vote is rejected and the owner of the address receives a fresh edit link (the old one stops working). Signed-in users are recognised automatically by their account email.

## The overview

- **Passt bisher am besten** shows the top options. Ranking: most *Yes*, then most *If need be*, then fewest *No*. Ties are all marked as best.
- The table shows every participant's answers with totals; the best column is highlighted. On phones the table scrolls sideways with the name column fixed.

## Managing a poll (organizer)

Open the admin link or, when signed in, **Verwalten** on the poll page (`/u/<id>/verwalten`).

- **Finalen Termin festlegen** – options are sorted by approvals; pick one and confirm. The poll closes for voting and **all participants and the organizer get an email with the date and an `.ics` calendar file**. The poll page shows the final date with an **In den Kalender** button.
- **Abstimmung wieder öffnen** – withdraw the final date and allow voting again (no emails are sent).
- **Teilnehmende** – see names and emails; remove entries.
- **Umfrage bearbeiten** – change title, location, description and the options above, remove proposals (their answers are deleted) or add new ones. The final option cannot be removed; at least one option must remain.
- **Umfrage löschen** – deletes the poll with all answers, irreversibly.

## Privacy

Participant emails are never shown publicly. No external fonts, scripts, trackers or ads are loaded. Cookies are used only for sign-in and to remember your own answer and organizer rights in this browser.
