const config = {
    //host: "https://my.skilltech.studio",
    auth: "https://auth.skilltech.tools",
    //host: "https://api.local",
    // Default language if user has no supported prefered language
    locale: 'en',
    // If no translation is found for the locale, use this locale
    // Important: all localised strings must be filled for this locale into i18n.js
    fallbackLocale: 'en',
    // List of supported locales
    locales: ['en', 'fr'],
    // Dimensions of the images thumbnails
    skyThumb: [150,75],
    // POI icons
    poiIcons: [
        {"title": "Question", "file": "question.png"},
        {"title": "Idea", "file": "idea.png"},
        {"title": "Place", "file": "place.png"},
        {"title": "Text", "file": "text.png"},
        {"title": "Image", "file": "image.png"},
    ],
    cursor: {
        linked: {
            src: "/v/img/cursor2.png"
        },
        unlinked: {
            src: "/v/img/cursor.png"
        }
    },
    skyMaxSize: 25000000,
    skyMinWidth: 2048,
    skyMaxWidth: 16384,

    skyThumbWidth: 400,
    skyThumbHeight: 250,

    skyPreloadWidth: 2048,
    skyPreloadHeight: 1024,

    imgMaxSize: 10000000,
    imgMaxWidth: 4096,

    poiThumbWidth: 200,
    tourThumbMaxSize: 100000,
    tourThumbWidth: 400,
    tourThumbHeight: 250,
    tourTitlemaxSize: 65,
    spotTitlemaxSize: 40,
    descriptionmaxSize: 250,
    demoDate: "2023-09-18 00:00:00",
}
export {config};
