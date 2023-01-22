/**ICI C'EST LES VARIABLES*/

import './styles/map.scss';

const latitude = document.getElementById('latitude').value;
const longitude = document.getElementById('longitude').value;
const zoomLevel = document.getElementById('zoomLevel').value;

const categorie = document.getElementById("categorie")
const subCategorie = document.getElementById("subCategorie")
const listeProduits = document.getElementById("search_result")

const greenIcon = new L.Icon({
    iconUrl: 'build/images/marker-icon-2x-green.png',
    shadowUrl: 'build/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});

const yellowIcon = new L.Icon({
    iconUrl: 'build/images/marker-icon-2x-gold.png',
    shadowUrl: 'build/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});

const redIcon = new L.Icon({
    iconUrl: 'build/images/marker-icon-2x-red.png',
    shadowUrl: 'build/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});

const map = L.map('map').setView([latitude, longitude], zoomLevel); 





/**ICI C'EST LE SCRIPT*/


setMap()
addYourMarker()
addProductMarker(coordinatesProduits);


//Affiche la sélection de sous-categories correspondant si une categorie à été choisie, et affiche des produits
categorie.addEventListener("change", function() { 
    if (categorie.value != "") {
        subCategorie.style.visibility = "visible";
        fetchSubcategories(categorie.value);
        fetchProduits(categorie.value, subCategorie.value)
        return
    }
    subCategorie.style.visibility = "hidden";
    fetchProduits(categorie.value, subCategorie.value)
})

//Affiche des produits si on change la sous categorie
subCategorie.addEventListener("change", function() { 
        fetchProduits(categorie.value, subCategorie.value)
})






/**ICI C'EST LES FONCTIONS */

/** RECHERCHE DE PRODUITS & AJAX*/ 

//on va fetch des sous-categories correspondante à la categorie selectionné
function fetchSubcategories(id) {
    return fetch('https://127.0.0.1:8000/produit/ajax/subcat/'+id)
        .then((response) => response.json())
        .then((json) => afficheSubcategories(json));
        
}

//on va fetch des produits correspondante à la categorie ou sous-categorie selectionné
function fetchProduits(idCat, idSubCat) {
    return fetch('https://127.0.0.1:8000/produit/ajax/produitcat/'+idCat+'/produitsubcat/'+idSubCat)
        .then((response) => response.json())
        .then((json) => afficheProduits(json));
}

//si notre selecteur à des options qu'on a crée, on les enleves, puis on recrée les nouvelles à partir de notre fetch
function afficheSubcategories(subCategories) {
    let options = document.getElementsByClassName("dynamicSubCat");
    let nbrOptionToRemove = options.length // on definie la longueur en dehors de la boucle, sinon on reduit l'iteration à chaque fois qu'on enleve un element
    for (let i=0; i < nbrOptionToRemove; i++) {
        options[0].remove(); //idem, on enleve que l'index 0, car js apparament réindexe les elements apres un remove
    }
    for (let i=0; i < subCategories.length; i++) {
        subCategorie.innerHTML+= '<option class="dynamicSubCat" value="'+subCategories[i].id+'">'+subCategories[i].name+'</option>'
        
    }
}

function afficheProduits(produits) {
    let cartesProduits = document.getElementsByClassName("product_card");
    let nbrProduitsToRemove = cartesProduits.length // on definie la longueur en dehors de la boucle, sinon on reduit l'iteration à chaque fois qu'on enleve un element
    for (let i=0; i < nbrProduitsToRemove; i++) {
        cartesProduits[0].remove(); //idem, on enleve que l'index 0, car js apparament réindexe les elements apres un remove
    }
    for (let i=0; i < produits.length; i++) {
        listeProduits.innerHTML+= 
            '<div class="product_card" id="'+produits[i].name+produits[i].id+'"><div class="product_desc"><div class="card_image"><img class="product_img" src="'+produits[i].imageURL+'" alt="product_image" width="200" height="200"></div><div class="card_text"><h3>'+produits[i].name+'</h3><a href="https://127.0.0.1:8000/entreprise/'+produits[1].entreprise.id+'">'+produits[1].entreprise.name+'</a><p>'+produits[i].desc+'</p></div></div><div class="card_btn"><a href="https://127.0.0.1:8000/devis/add/'+produits[i].id+'"><button class="btn_link_green">Ajouter à votre liste de commande</button><a></div></div>'
        ;
        
    }
}


/**MAP*/

function setMap(){
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);
}

function addYourMarker(){
    L.marker([latitude, longitude], {icon: redIcon, title: "you"}).addTo(map)
}

function addProductMarker(coordinates) {
    for (let i=0; i < coordinates.length; i++){
        coordinates[i] = coordinates[i].split(",");
        
        const lat = coordinates[i][0];
        const long = coordinates[i][1];
        const nomEntreprise = coordinates[i][2]
        const idEntreprise = coordinates[i][3]

        let distance= map.distance([latitude, longitude],[lat, long])
        distance = Math.ceil(distance/1000)

        L.marker([lat, long], {title:idEntreprise+"|"+distance}).addTo(map).bindPopup("<a href='/entreprise/"+idEntreprise+"'>"+nomEntreprise+"</a><p>Distance: "+distance+"km</p>");
        document.getElementsByClassName(idEntreprise+nomEntreprise)[0].innerText = "Distance: "+distance+"km";
    }
}