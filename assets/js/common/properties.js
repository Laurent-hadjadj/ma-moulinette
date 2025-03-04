/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) Lilmod & Lelamed - 2015-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

/**
 * On renvoie l'adresse du serveur ou est installée l'application !
 * L'adresse doit être modifiée en fonction du serveur.
 * */
export const serveur=function () {
    // Récupérer l'URL depuis la balise meta
    const metaUrl = document.querySelector('meta[name="url"]');
    // Retourner l'URL trouvée dans la balise meta ou, par défaut, l'origine de la location
    return metaUrl ? metaUrl.getAttribute('content') : location.origin;
};

export const serveur_uri= async () => {

    try{
        /** On récupère le path pathname de la page depuis une requête */
        const response = await fetch(window.location.pathname);
        /** On récupère le paramètre xSubpathLocation utilisé pour définir un environnement avec reverse-proxy */
        const xSubpathLocation = response.headers.get('X-Subpath-Location');
        const origin=location.origin;

        /** On fonction de la réponse on construit l'URL avec un préfixe ou non */
        if (typeof xSubpathLocation !== 'undefined' &&  xSubpathLocation !== null  && xSubpathLocation !== '') {
            return origin + xSubpathLocation;
            } else {
                return location.origin;
            }
        } catch(error){
            sessionStorage.setItem('error', `Error fetching data :  ${JSON.stringify(error, null, 2)}}`);
            throw error;
        }
    };
