/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 * Fork du code, réécirure des rglès de calcul du score.
 * @link https://github.com/elboletaire/password-strength-meter
 * @license GPL-3.0
 */

// eslint-disable-next-line

(function($) {
  'use strict';

  const Password = function($object, options) {
    const defaults = {
      enterPass: 'Saisir un mot de passe.',
      shortPass: 'Mot de passe trop court.',
      containsField: 'Le mot de passe contient le nom/prénom.',
      steps: {
        19: 'Médiocre.',
        39: 'Mauvais.',
        45: 'tout juste correcte.',
        50: 'Juste bonne.',
        59: 'Bonne.',
        79: 'Très bonne.',
        90: 'Qualité solide.',
        95: 'Qualité très solide.',
      },
      showPercent: false,
      showText: true,
      animate: true,
      animateSpeed: 'fast',
      minimumLength: 10,
      closestSelector: 'div',
      useColorBarImage: false,
      customColorBarRGB: {
        red: [0, 240],
        green: [0, 240],
        blue: 10
      },
      classOption: 'open-sans',
    };

    /** Fusion des options avec les paramètres par défaut */
    options = $.extend({}, defaults, options);

    /** Regex Pattern
      * OK : Nombre de caractère                              +(n*4)
      * OK : Lettres majuscules                               +((len-n)*2)
      * OK : Lettres minuscules                               +((len-n)*2)
      * OK : Chiffres                                         +(n*4)
      * OK : Symboles                                         +(n*6)
      * ok : Nombres ou symboles entre [a-aA-Z]               +(n*2)
      * OK : Exigences (11 car, 2 maj, 2 min, 2 num 2 symb)   +(n*2)
      * OK : Lettres uniquement                               -n
      * OK : Chiffres seulement                               -n
      * OK : Caractères répétés                               -
      * OK : Lettres majuscules consécutive                   -(n*2)
      * OK : Lettres minuscules consécutives                  -(n*2)
      * OK : Chiffres consécutifs                             -(n*2)
      * OK : Lettres séquentielles (3+)                       -(n*3)
      * OK : Chiffres séquentiels (3+)                        -(n*3)
      * OK : recherche d'une date (YYYY)
    */

    const passwordHas3Numbers=/(.*[0-9].*[0-9].*[0-9])/;
    // ~!€¤£§'"@#$&*?|%+-<>_.,\/:;=()[]{}^ =>35
    const symbole = /([~!€¤£§'"@#$&*?|%\+\-<>_.,\\/:;=()\[\]{}^\s])/g;
    const minusMajuscule=/([a-z].*[A-Z])|([A-Z].*[a-z])/g;
    const lettreMajuscule=/([A-Z])/g;
    const lettreMinuscule=/([a-z])/g;
    const lettre=/([a-zA-Z])/g;
    const chiffre=/([0-9])/g;
    const queDesMinuscule=/^[[:lower:]]+$/;
    const queDesMajuscule=/^[[:upper:]]+$/;
    const queDesLettres=/^\w+$/;
    const queDesChiffres=/^\d+$/;
    const lettreConsecutiveMajuscule=/([A-Z])\1+/g;
    const lettreConsecutiveMinuscule=/([a-z])\1+/g;
    const chiffreConsecutif=/([0-9])\1+/g;
    const dateAnnee=/(19\d\d|200\d|201\d|202\d|203\d)/g;
    const dateCompleteSansSeparateur=/^\d{4,8}$/u;
    const dateCompleteAvecSeparateur=/^(\d{1,4})([\s\\/_.-])(\d{1,2})\2(\d{2,4})$/u;
    const alefbet = 'abcdefghijklmnopqrstuvwxyz';
    const undeuxtrois= '01234567890';
    const  L33t={
          'a': ['4', '@'],
          'b': ['8'],
          'c': ['(', '{', '[', '<'],
          'e': ['3'],
          'g': ['6', '9'],
          'i': ['1', '!', '|'],
          'l': ['1', '|', '7'],
          'o': ['0'],
          's': ['$', '5'],
          't': ['+', '7'],
          'x': ['%'],
          'z': ['2'] };

    /**
      * [Description for calculRepetition]
      *
      * Calcul de la déduction de l'incrément en fonction de la proximité de
      * caractères identiques. La déduction est incrémentée chaque fois  qu'une
      * nouvelle correspondance est découverte. Le niveau de la déduction est
      * basé sur la longueur totale du mot de passe divisée par la différence
      * de distance entre la correspondance actuellement sélectionnée.
      *
      * @param {array} groupe
      *
      * @return {number}
      *
      * Created at: 14/01/2024 16:09:51 (Europe/Paris)
      * @author     Laurent HADJADJ <laurent_h@me.com>
      * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
      */
      const calculRepetition = function(password) {
        let nRepInc = 0;
        let nRepChar = 0;
        let nUnqChar = 0;

        for (let a = 0; a < password.length; a++) {
          let bCharExists = false;
          for (let b = 0; b < password.length; b++) {
            if (password.charAt(a) == password.charAt(b) && a !== b) {
              bCharExists = true;
              nRepInc += Math.abs(password.length / (b - a));
            }
          }

          if (bCharExists) {
            nRepChar++;
            nUnqChar = password.length - nRepChar;
            nRepInc = (nUnqChar) ? Math.ceil(nRepInc / nUnqChar) : Math.ceil(nRepInc);
          }
        }
        return nRepInc;
      }

      /**
       * [Description for reverse]
       *  Fonction d'invertion d'une sequence de caractères
       *
       * @param {string} str
       *
       * @return {string}
       *
       * Created at: 16/01/2024 18:29:03 (Europe/Paris)
       * @author     Laurent HADJADJ <laurent_h@me.com>
       * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
       */
      function reverse(str) {
        return str.split('').reverse().join('');
      }

      /**
       * [Description for calculSuiteLettre]
       * Calcul le score de n répétition de n caractère consécutive.
       *
       * @param {array} groupe
       *
       * @return {number}
       *
       * Created at: 14/01/2024 18:51:22 (Europe/Paris)
       * @author     Laurent HADJADJ <laurent_h@me.com>
       * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
       */
      const calculSuiteCaractere=function(groupe){
      let n=0;
      for (const element of groupe){
        n +=element.length-1;
      }
      return n;
    }

    /**
     * [Description for scoreText]
     *
     * @param {number} score Score de base (0, -1 ou -2).
     *
     * @return [type]
     *
     * Created at: 12/01/2024 09:11:28 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    const scoreText=function(score) {

      /** si score <O alors score=0 sinon score */
      score = score < 0 ? 0 : score;

      let text = options.shortPass;
      const sortedStepKeys = Object.keys(options.steps).sort();
      for (let step in sortedStepKeys) {
        const stepVal = sortedStepKeys[step];
        if (stepVal < score) {
          text = options.steps[stepVal];
        }
      }
      return text;
    }

    /**
     * [Description for calculateScore]
     * Calcul de la solidité du mot de passe en fonctions des règles :
     *
     * @param  {string} password Le mot de passe à analyser
     *
     *
     * @return {number} score = -2 à 100
     *
     * Created at: 12/01/2024 09:18:21 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    const calculateScore=function(password) {
      let score = 0, resultat={};

      /** on split chaque caractère du mot de passe dans un tableau */
      const tabPassword= password.match(/./g);

      /** Le mot de passe est plus petit que la taille recommandée
       * (password < options.minimumLength
       */
      if (password.length < options.minimumLength) {
        return -10;
      }

      /** Nombre de caractères +(n*4) */
      const NombreCaractere = parseInt(password.length,10);
      score += NombreCaractere*4;
      resultat['NombreCaractere']=NombreCaractere*4;

      /** Nombre de Lettres en majuscules +((len-n)*2) */
      const NombreLettreMajuscule = NombreCaractere-parseInt(password.replace(lettreMajuscule, '').length,10);
      score += (NombreCaractere-NombreLettreMajuscule)*2;
      resultat['NombreLettreMajuscule'] = (NombreCaractere-NombreLettreMajuscule)*2;

      /** Nombre de lettres en minuscules +((len-n)*2) */
      const NombreLettreMinuscule = NombreCaractere-parseInt(password.replace(lettreMinuscule, '').length,10);
      score += (NombreCaractere-NombreLettreMinuscule)*2;
      resultat['NombreLettreMinuscule'] = (NombreCaractere-NombreLettreMinuscule)*2;

      /** Nombre de chiffres +(n*4) */
      const NombreChiffre = NombreCaractere-parseInt(password.replace(chiffre, '').length,10);
      score += NombreChiffre*4;
      resultat['NombreChiffre']=NombreChiffre*4;

      /** Nombre de symboles  +(n*6) */
      const NombreSymbole = NombreCaractere-parseInt(password.replace(symbole, '').length,10);
      score += NombreSymbole*6;
      resultat['NombreSymbole']=NombreSymbole*6;

      /** Nombre de midChar +(n*2) */
      // le premier caractere ne peut pas être un chiffre : a1xxxxx =0
      // un ou des chiffres doivent être encadré par des lettres : a1n1a =2
      // si on a un groupe de chiffre encadré on compte le nombre de chiffre : a11n1a = 3
      let nombreAuMilieu=0;
      if (NombreCaractere>=options.minimumLength){
        for (let c=0; c<password.length; c++) {
          if (tabPassword[c].match(chiffre)){
            if (c > 0 && c < (password.length - 1)) {
              nombreAuMilieu++;
            }
          }
        }
      }

      resultat['NombreAuMilieu']=0;
      if (nombreAuMilieu > 0) {
          score += (nombreAuMilieu-1)*2;
          resultat['NombreAuMilieu']=(nombreAuMilieu-1)*2;
      }

      /** Nombre de midChar +(n*2) */
      // le premier caractere ne peut pas être un chiffre : a1xxxxx =0
      // un ou des chiffres doivent être encadré par des lettres : a1n1a =2
      // si on a un groupe de chiffre encadré on compte le nombre de chiffre : a11n1a = 3
      let symboleAuMilieu=0;
      if (NombreCaractere>=options.minimumLength){
        for (let c=0; c<password.length; c++) {
          if (tabPassword[c].match(symbole)){
            if (c > 0 && c < (password.length - 1)) {
              symboleAuMilieu++;
            }
          }
        }
      }

      resultat['SymboleAuMilieu']=0;
      if (symboleAuMilieu > 0) {
          score += (symboleAuMilieu-1)*2;
          resultat['SymboleAuMilieu']=(symboleAuMilieu-1)*2;
      }

      /** L'exigence est :
       * - longueur 11 caractères
       * - des majuscules, au moins 2
       * - des minuscules, au moins 2
       * - des chiffres, au moins 2
       * - des symboles, au moins 2
       * */

      let n=0;
      resultat['Exigence']=0;
      if (NombreCaractere>=11 && NombreLettreMajuscule>0 && NombreLettreMinuscule>0 && NombreChiffre>0 && NombreSymbole>0){
        const Nombre2Majuscule=Math.trunc(NombreLettreMajuscule/2);
        const Nombre2Minuscule=Math.trunc(NombreLettreMinuscule/2);
        const Nombre2Chiffre=Math.trunc(NombreChiffre/2);
        const Nombre2Symbole=Math.trunc(NombreSymbole/2);

        n= Nombre2Majuscule + Nombre2Minuscule + Nombre2Chiffre + Nombre2Symbole;

        score += n*2;
        resultat['Exigence']=n*2;
      }

      /** Lettres uniquement -n */
      resultat['QueDesLettres']=0
      if (NombreCaractere>=options.minimumLength && queDesLettres.test(password)) {
        score += -NombreCaractere;
        resultat['QueDesLettres']=-NombreCaractere;
      }

      /** Chiffres uniquement -n */
      resultat['QueDesChiffres']=0
      if (NombreCaractere>=options.minimumLength && queDesChiffres.test(password)) {
        score += -NombreCaractere;
        resultat['QueDesChiffres']=-NombreCaractere;
      }

      /** Caractères identiques répétés -nRepInc */
      resultat['RepetitionIndentique']=0;
      if (NombreCaractere>=options.minimumLength){
        const n=calculRepetition(password);
        score += -n;
        resultat['RepetitionIndentique']=-n;
      }

      /** Lettres majuscules consécutives -(n*2) */
      resultat['RepetitionLettreMajuscule']=0;
      if (NombreCaractere>=options.minimumLength){
        const groupe = password.match(lettreConsecutiveMajuscule);
        if (groupe!=null) {
          const n=calculSuiteCaractere(groupe);
          score += -n*2;
          resultat['RepetitionLettreMajuscule']=-n*2;
          console.log('Repetition Majuscule : ', n);
        }
      }

      /** Lettres minuscules consécutives -(n*2) */
      resultat['RepetitionLettreMinuscule']=0;
      if (NombreCaractere>=options.minimumLength){
        const groupe = password.match(lettreConsecutiveMinuscule);
        if (groupe!=null) {
          const n=calculSuiteCaractere(groupe);
          score += -n*2;
          resultat['RepetitionLettreMinuscule']=-n*2;
        }
      }

      /** Chiffres consécutifs -(n*2) */
      resultat['repetitionChiffre']=0;
      if (NombreCaractere>=options.minimumLength){
        const groupe = password.match(chiffreConsecutif);
        if (groupe!=null) {
          const n=calculSuiteCaractere(groupe);
          score += -n*2;
          resultat['repetitionChiffre']=-n*2;
        }
      }

      /** Recherche d'une date comprise entre 1900 et 2039 -(4n) */
      resultat['DateAnnee']=0
      if (NombreCaractere>=options.minimumLength && dateAnnee.test(password)) {
        const resultatDateAnnee=password.match(dateAnnee);
        score += -resultatDateAnnee.length;
        resultat['DateAnnee']=-resultatDateAnnee.length;
      }

    /** Rechecher une potentielle date sans séparateur
     * La date à une longueur de 4 (1124) à 8 (10122024)
    */
    resultat['DateSansSepareteur']=0
    if (NombreCaractere>=options.minimumLength && dateCompleteSansSeparateur.test(password)) {
      const resultatDateCompleteSansSeparateur=RegExp(dateCompleteSansSeparateur).exec(password);
      score += -resultatDateCompleteSansSeparateur.length;
      resultat['DateSansSepareteur']=-resultatDateCompleteSansSeparateur.length;
    }

    /** Recherche une potentielle date avec séparateur (/\-_.)
     * La date à une longueur de 6 (1/1/24) à 10 (10/12/2024)
    */
    resultat['DateAvecSeparateur']=0;
    if (NombreCaractere>=options.minimumLength && dateCompleteAvecSeparateur.test(password)) {
      const resultatDateCompleteAvecSeparateur=RegExp(dateCompleteAvecSeparateur).exec(password);
      if (parseInt(resultatDateCompleteAvecSeparateur[0],10)!=0 &&
          parseInt(resultatDateCompleteAvecSeparateur[0],10)<=31 &&
          parseInt(resultatDateCompleteAvecSeparateur[1],10)!=0 &&
          parseInt(resultatDateCompleteAvecSeparateur[1],10)<=12 &&
          parseInt(resultatDateCompleteAvecSeparateur[3],10)!=0){
            score += -resultatDateCompleteAvecSeparateur.length;
            resultat['DateAvecSeparateur']=-resultatDateCompleteAvecSeparateur.length;
          }
    }

    /*  Sequence differentes de lettres (3+)++ -(n*3)*/
    resultat['SuiteLettreSequentielle']=0;
    if (NombreCaractere>=options.minimumLength && lettre.test(password)) {
      let croissant, decroissant, sequence=0;

      for (let s=0; s < 23; s++) {
        croissant = alefbet.substring(s,parseInt(s+3));
        decroissant = reverse(croissant);
        if (password.toLowerCase().indexOf(croissant) != -1 || password.toLowerCase().indexOf(decroissant) != -1) {
          sequence++;
        }
        score += -sequence*3;
        resultat['SuiteLettreSequentielle']=-sequence*3;
      }
    }

    /*  Sequence differentes de chiffres (3+)++ -(n*3) */
    resultat['SuiteChiffreSequentielle']=0;
    if (NombreCaractere>=options.minimumLength && chiffre.test(password)) {
      let croissant, decroissant, sequence=0, groupe=0;
      for (let s=0; s < 8; s++) {
        croissant = undeuxtrois.substring(s,parseInt(s+3));
        decroissant = reverse(croissant);
        if (password.indexOf(croissant) != -1 || password.indexOf(decroissant) != -1) {
          sequence++;
        }
      }
      score += -sequence*3;
      resultat['SuiteChiffreSequentielle']=-sequence*3;
    }

    let malus=0, bonus=0;
    /** 8 caractères = -50 Malus */
    if (NombreCaractere === 8) {
      score= score-50
      malus=-50
    }
    /** 9 caractères = -30 Malus */
    if (NombreCaractere === 9) {
      score= score-20
      malus=-50
    }
    /** 10 caractères = -10 Malus */
    if (NombreCaractere === 10) {
      score= score-10
      malus=-50
    }
    /** 11 caractères = +10 Bonus */
    if (NombreCaractere === 11) {
      score= score+5
      bonus=5
    }

    resultat['bonus']=bonus;
    resultat['malus']=malus;

    /** si on est très bon on aura 100 points */
    if (score > 100) {
      score = 100;
    }

    /** si on est très mauvais on aura 0 point */
    if (score < 0) {
      score = 0;
    }
    //console.info(resultat);
    return score;
    }

    /**
     * [Description for calculateColorFromPercentage]
     * Calcule la barre de couluers en fonction du pourcentage.
     *
     * @param {number} perc Le pourcentage de solidité du mot de passe.
     *
     * @return {object} Retourne un objet clé/valeur avec comme clé la couleur
     *
     * Created at: 12/01/2024 14:13:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    const calculateColorFromPercentage=function(perc) {
      let minRed = 0, maxRed = 240, minGreen = 0, maxGreen = 240, blue = 10;

      if (Object.hasOwn(options.customColorBarRGB, 'red')) {
        minRed = options.customColorBarRGB.red[0];
        maxRed = options.customColorBarRGB.red[1];
      }

      if (Object.hasOwn(options.customColorBarRGB, 'green')) {
        minGreen = options.customColorBarRGB.green[0];
        maxGreen = options.customColorBarRGB.green[1];
      }

      if (Object.hasOwn(options.customColorBarRGB, 'blue')) {
        blue = options.customColorBarRGB.blue;
      }

      const green = (perc * maxGreen / 50);
      const red = (2 * maxRed) - (perc * maxRed / 50);

      return {
        red: Math.min(Math.max(red, minRed), maxRed),
        green: Math.min(Math.max(green, minGreen), maxGreen),
        blue: blue
      }
    }

    /**
     * [Description for addColorBarStyle]
     *
     * @param {jQuery} $colorbar L'objet jquery colorbar.
     * @param {number} perc Le pourcentage de solidité du mot de passe.
     *
     * @return {jQuery}
     *
     * Created at: 12/01/2024 14:19:19 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    const addColorBarStyle=function($colorbar, perc) {
      if (options.useColorBarImage) {
        $colorbar.css({
          backgroundPosition: `0px -${perc}px`,
          width: perc + '%'
        });
      } else {
        const colors = calculateColorFromPercentage(perc);
        $colorbar.css({
          'background-image': 'none',
          'background-color': 'rgb(' + colors.red.toString() + ', ' + colors.green.toString() + ', ' + colors.blue.toString() + ')',
          width: perc + '%'
        });
      }

      return $colorbar;
    }

    /**
     * [Description for init]
     * Initialise le plugin jquery, créé et attache les différents évenements.
     * @return {Password} L'instance de Password
     *
     * Created at: 12/01/2024 14:23:14 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    const init=function(){
      let shown = true;
      let $text = options.showText;
      let $percentage = options.showPercent;
      const ClassOption = options.classOption;

      let $graybar = $('<div>').addClass('pass-graybar');
      let $colorbar = $('<div>').addClass('pass-colorbar');
      /** On ajoute une class CSS pour surcharger le CSS (ex. la police). */
      let $insert = $('<div>').addClass(`pass-wrapper ${ClassOption}`).append(
        $graybar.append($colorbar)
      );

      $object.closest(options.closestSelector).addClass('pass-strength-visible');
      if (options.animate) {
        $insert.css('display', 'none');
        shown = false;
        $object.closest(options.closestSelector).removeClass('pass-strength-visible');
      }

      if (options.showPercent) {
        $percentage = $('<span>').addClass('pass-percent').text('0% ');
        $insert.append($percentage);
      }

      if (options.showText) {
        $text = $('<span>').addClass('pass-text').html(options.enterPass);
        $insert.append($text);
      }

      $object.closest(options.closestSelector).append($insert);

      $object.keyup(function() {
        let field = options.field || '';
        if (field) {
          field = $(field).val();
        }

        let score = calculateScore($object.val(), field);
        $object.trigger('password.score', [score]);
        let perc = score < 0 ? 0 : score;

        $colorbar = addColorBarStyle($colorbar, perc);

        /** On chnage la couleur du pourcentage en fonction de la qualité du mot de passe */
        if (options.showPercent) {
          const colors = calculateColorFromPercentage(perc);
          const ColorRed=(colors.red-10).toString();
          const ColorGreen=(colors.green-60).toString();
          const ColorBlue=10;

          $percentage.css({
            'color': 'rgb(' + ColorRed + ', ' + ColorGreen + ', ' + ColorBlue + ')', width: perc + '%'
          });
          $percentage.html(perc + '% ');
        }

        if (options.showText) {
          let text = scoreText(score);
          if (!$object.val().length && score <= 0) {
            text = options.enterPass;
          }

          if ($text.html() !== $('<div>').html(text).html()) {
            $text.html(text);
            $object.trigger('password.text', [text, score]);
          }
        }
      });

      if (options.animate) {
        $object.focus(function() {
          if (!shown) {
            $insert.slideDown(options.animateSpeed, function () {
              shown = true;
              $object.closest(options.closestSelector).addClass('pass-strength-visible');
            });
          }
        });

        $object.blur(function() {
          if (!$object.val().length && shown) {
            $insert.slideUp(options.animateSpeed, function () {
              shown = false;
              $object.closest(options.closestSelector).removeClass('pass-strength-visible')
            });
          }
        });
      }

      return this;
    }

    return init.call(this);
  };

  // Bind to jquery
  $.fn.password = function(options) {
    return this.each(function() {
      new Password($(this), options);
    });
  };
})(jQuery);
