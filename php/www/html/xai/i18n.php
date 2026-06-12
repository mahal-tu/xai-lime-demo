<?php
// Internationalization

function i18n_get_lang() {
  if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
  } else {
    $lang = 'en';
  }
  return ($lang === 'de') ? 'de' : 'en';
}

function i18n($key) {
  static $lang = null;
  if ($lang === null) {
    $lang = i18n_get_lang();
  }
  $translations = [
    'de' => [
      // General
      'Weiter' => 'Weiter',
      'FensterSchliessen' => 'Sie können das Fenster jetzt schließen.',
      'FensterSchliessenAlt' => 'Ansonsten können Sie das Fenster jetzt schließen.',
      'KIExplainableTitle' => 'Verstehbare KI - Explainable Artificial Intelligence',
      'KIHead' => 'Verstehbare KI - XAI',
      'WelcomeText' => 'Herzlich Willkommen bei unserer Studie zur verstehbaren KI!',
      'IntroText1' => 'Im Folgenden zeigen wir Ihnen Bilder von <b>handgeschriebenen Ziffern</b>, die von zwei KIs erkannt werden sollen. Eine der KIs ist dabei sehr viel besser als die andere.',
      'IntroText2' => 'Wir zeigen Ihnen, auf welche Bereiche in den Bildern die KIs besonders geachtet haben.',
      'IntroText3' => 'Ihre Aufgabe ist es, <b>die bessere, vertrauenswürdigere KI</b> auszuwählen.',
      'IntroText4' => 'Am Ende haben Sie die Möglichkeit, an einer Verlosung eines Einkaufsgutscheins über 50€ teilzunehmen.',
      'IntroText5' => 'Die Teilnahme dauert <b>ca. 5 Minuten</b>. Wir beginnen mit vier Trainingsdurchgängen.',
      'StartButton' => 'Auf geht\'s',
      'LotteryText' => 'Wenn Sie an der Verlosung teilnehmen möchten, geben Sie hier Ihre E-Mail an.',
      'ProgressEnde' => 'Ende',
      'EndeDanke' => 'Vielen Dank!',
      // Trials
      'KITrainiert' => 'Zwei unterschiedliche KI-Systeme wurden darauf trainiert, Ziffern zu erkennen.',
      'BessereKI' => 'Welche KI ist besser?',
      'WaehlenAus' => 'Wählen Sie aus!',
      'ErklaerungHAF' => 'A ist die beste Vorhersage der KI, B ist die zweitbeste. Die Farbkodierung gibt an, welche Bereiche für die KI besonders wichtig waren. Grüne Bereiche sprechen für die ausgewählte erkannte Ziffer. Rote Bereiche sprechen dagegen.',
      'ErklaerungMAF' => 'A ist die beste Vorhersage der KI, B ist die zweitbeste. Die Maskierung gibt an, welche Bereiche für die KI besonders wichtig waren. Je dunkler der Bereich, desto wichtiger.',
      'Richtig' => 'Das war <b>richtig</b>!',
      'Falsch' => 'Das war leider <b>falsch</b>.',
      'TrainingAbgeschlossen' => 'Damit ist das <b>Training abgeschlossen</b>.<br>Bitte schauen Sie sich die folgenden Bildpaare an und entscheiden Sie!',
      'KIVergleichen' => 'KIs vergleichen',
      'Training' => 'Training',
      // Knowledge
      'AbkuerzungFrage' => 'Kennen Sie die Abkürzung? Wählen Sie aus!',
      'KIWissen' => 'KI-Wissen',
      'Danke' => 'Vielen Dank! Das Meiste haben Sie schon geschafft.',
      'KIWissenIntro' => 'Im nächsten Abschnitt geht es um Ihr <b>KI-Wissen</b>.',
      'KIWissenErklaerung' => 'Sie sehen verschiedene Abkürzungen aus dem KI-Bereich. Kennen Sie ihre Bedeutung?',
      'WasBedeutetBeg' => 'Was bedeuted',
      'WasBedeutetEnd' => '?',
      'WeissNicht' => 'Weiß nicht',
      // Demographics
      'DemographicsHeader' => 'Demographische Angaben',
      'DemographicsIntro' => 'Im letzten Abschnitt fragen wir nach Ihren demographischen Angaben.',
      'DemographicsVoluntary' => 'Die Antwort ist natürlich freiwillig!',
      'DemographicsFrage' => 'Sie können uns helfen, indem Sie uns noch etwas über sich selbst erzählen. Diese Angaben sind komplett freiwillig!',
      'DemographicsAlter' => 'Alter',
      'DemographicsGender' => 'Geschlecht',
      'DemographicsGenderM' => 'm',
      'DemographicsGenderF' => 'w',
      'DemographicsGenderD' => 'd',
      'DemographicsEducation' => 'Höchster Bildungsabschluss',
      'DemographicsEduGrund' => 'Grundschule (4-6 Schuljahre)',
      'DemographicsEduOber' => 'Oberschule (7-10 Schuljahre)',
      'DemographicsEduAbi' => 'Abitur',
      'DemographicsEduHochB' => 'Hochschulabschluss (Bachelor)',
      'DemographicsEduHochM' => 'Hochschulabschluss (Master)',
      'DemographicsEduPhD' => 'Promotion',
      'SubmitButton' => 'Abschicken',
    ],
    'en' => [
      // General
      'Weiter' => 'Continue',
      'FensterSchliessen' => 'You can now close the window.',
      'FensterSchliessenAlt' => 'Otherwise, you can now close the window.',
      'KIExplainableTitle' => 'Explainable Artificial Intelligence',
      'KIHead' => 'Explainable AI - XAI',
      'WelcomeText' => 'Welcome to our study on explainable AI!',
      'IntroText1' => 'We will show you images of <b>handwritten digits</b> that two AI systems are trying to recognize. One of the AIs is much better than the other.',
      'IntroText2' => 'We will show you which areas of the images the AIs paid special attention to.',
      'IntroText3' => 'Your task is to select <b>the better, more trustworthy AI</b>.',
      'IntroText4' => 'At the end, you have the opportunity to participate in a lottery for a €50 shopping voucher.',
      'IntroText5' => 'Participation takes <b>about 5 minutes</b>. We start with four training rounds.',
      'StartButton' => 'Let\'s go',
      'LotteryText' => 'If you want to participate in the lottery, please enter your email here.',
      'ProgressEnde' => 'End',
      'EndeDanke' => 'Thank you!',
      // Trials
      'KITrainiert' => 'Two different AI systems were trained to recognize digits.',
      'BessereKI' => 'Which AI is better?',
      'WaehlenAus' => 'Please select!',
      'ErklaerungHAF' => 'A is the best prediction of the AI, B is the second best. The color coding shows which areas were especially important for the AI. Green areas support the selected digit, red areas speak against it.',
      'ErklaerungMAF' => 'A is the best prediction of the AI, B is the second best. The masking shows which areas were especially important for the AI. The darker the area, the more important.',
      'Richtig' => 'That was <b>correct</b>!',
      'Falsch' => 'Unfortunately, that was <b>wrong</b>.',
      'TrainingAbgeschlossen' => '<b>Training is complete.</b><br>Please look at the following image pairs and decide!',
      'KIVergleichen' => 'Compare AIs',
      'Training' => 'Training',
      // Knowledge
      'AbkuerzungFrage' => 'Do you know the acronym? Please select!',
      'KIWissen' => 'AI Knowledge',
      'Danke' => 'Thank you! You have already completed most of the study.',
      'KIWissenIntro' => 'The next section is about your <b>AI knowledge</b>.',
      'KIWissenErklaerung' => 'You will see various acronyms from the AI field. Do you know their meaning?',
      'WasBedeutetBeg' => 'What does',
      'WasBedeutetEnd' => ' mean?',
      'WeissNicht' => 'Don\'t know',
      // Demographics
      'DemographicsHeader' => 'Demographic Information',
      'DemographicsIntro' => 'In the last section, we ask for your demographic information.',
      'DemographicsVoluntary' => 'Answering is of course voluntary!',
      'DemographicsFrage' => 'You can help us by telling us a bit about yourself. This information is completely voluntary!',
      'DemographicsAlter' => 'Age',
      'DemographicsGender' => 'Gender',
      'DemographicsGenderM' => 'm',
      'DemographicsGenderF' => 'f',
      'DemographicsGenderD' => 'd',
      'DemographicsEducation' => 'Highest educational qualification',
      'DemographicsEduGrund' => 'Primary school (4-6 school years)',
      'DemographicsEduOber' => 'Middle school (7-10 school years)',
      'DemographicsEduAbi' => 'High school diploma',
      'DemographicsEduHochB' => 'University degree (Bachelor)',
      'DemographicsEduHochM' => 'University degree (Master)',
      'DemographicsEduPhD' => 'PhD',
      'SubmitButton' => 'Submit',
    ],
  ];
  return $translations[$lang][$key] ?? $key;
}
