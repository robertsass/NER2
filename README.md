# Movie Title Recognition

## About the project

Ruprecht-Karls-Universität Heidelberg
Institut für Computerlinguistik


Filmtitel

Lilitta Muradjan, Robert Saß, Theresa Sick
1. Mai 2016


Proseminar
"Designing Experiments for Machine Learning Tasks“
bei Frau Éva Mújdricza-Mayt
Wintersemester 2015/2016


Organisatorisches

Mitglieder
Lilitta Muradjan	75% Computerlinguistik, 25% Spanisch
Theresa Sick		75% Computerlinguistik, 25% Anglistik
Robert Saß		75% Computerlinguistik, 25% Soziologie
Zeiteinteilung
Unsere Vorbereitungsphase soll bis Anfang Januar dauern. In diesem Zeitraum planen wir die  Vorgehensweise und das Sammeln geeigneter Test- und Trainingsdaten. Diese werden anschließend annotiert. Für unser Projekt ist es wichtig Muster in den Satzstrukturen und der Umgebung der Filmtitel  zu kennen. Diese genaue Analyse wird uns bei der Auswahl der richtigen Featrues zu Hilfe kommen.
Unsere zweite Phase schließt im Januar direkt an unsere Vorbereitungen an. Wir wollen das Projekt so weit ausarbeiten, dass wir Ende des Monats ein Baseline Experiment durchführen und unser Projekt dem Seminar vorstellen können.
In der dritten Phase soll unser Projekt fertig gestellt und die Genauigkeit des Algorithmus optimiert werden.
Ziel
In redationellen Texten (z.B. Filmkritiken) sollen alle Okkurenzen von Filmtiteln hervorgehoben werden.Resourcen
Film-Datenbanken:
Benutzen wir in der Trainingsphase und zur automatischen Selbstkontrolle
IMDB
Facebook Graph-API
Synonym-Datenbanken
Diese Online-Datenbanken ziehen wir hinzu, um in der Vorverarbeitung der Daten Synonyme von Prädikaten zu erkennen.
woxikon.de
synonyme.de
duden.de
openthesaurus.de
Sonstige Datenbanken
Wiktionary (zur Klassifikation von Wortarten)
Wikipedia (als Ergänzung zu den Film-Datenbanken)
GermaNet (zur Klassifikation von Senses)

Es gibt einige Projekte mit ähnlichen Vorhaben, aus deren Fehlern und Schwierigkeiten wir lernen können. Außerdem ist es sehr interessant wie sich die Fehler in Deutschen und Englisch Projekten unterscheiden.Featureauswahl
Unsere Featureauswahl wird sehr von der Analyse der Trainingsdaten abhängen.
Diese hoffen wir in den deutschen Texten zu finden und für unser Projekt nutzen zu können:
Groß- und Kleinschreibung
Satz-Einbettung
Sonderzeichen in der Umgebung
Prädikatstellung

Baseline Experiment
Als Baseline Experiment eignet sich für unser Projekt ein Majority-Experiment. Es soll festgestellt werden, welche Klasse die meisten Instanzen haben. Bei unserem Projekt wird das eine binäre Entscheidung zwischen Titel bzw nicht Titel. Wir versuchen bei den Test- und Trainingsdaten von mindestens 50% zu erreichen.

Ausblick
Wir wollen erreichen, dass Filmtitel korrekt erkannt und hervorgehoben werden. 
Da wir mit möglichen sprachlichen/grammatischen Fehlern in unseren Daten konfrontiert sein werden, ist ein weiteres Ziel so viel umgangssprachliche Fehler wie möglich richtig zu interpretieren.
Falls wir zum Ende des Projekts noch Zeit haben würden wir unser Projekt gern auf nicht-redaktionelle Texte oder Musiktitel o.ä. ausweiten. Dann könnte man auch Twitter oder Facebook als Datenpool hinzuziehen.
Momentaner Stand
Wir stecken momentan noch in der Findungs-/Vorbereitungsphase. Allerdings haben wir vor im neuen Jahr mit der Ausarbeitung zu beginnen.




## Code documentation
http://www.filmtitel.xyz/docs/api/
