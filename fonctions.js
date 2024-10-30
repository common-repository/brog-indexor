//Merci à Damien Alexandre pour ce script : http://damienalexandre.fr/Info-Bulle-en-Javascript.html
var bi_bulle=false;
function GetId(id) {
	return document.getElementById(id);
}
function move(e) {
	if(bi_bulle) {
		if (navigator.appName!="Microsoft Internet Explorer") {
			GetId("bi_curseur").style.left=e.pageX + 10+"px"; //modifier les valeurs ici pour changer la place de l'infobulle, change here the values to move the popup.
			GetId("bi_curseur").style.top=e.pageY + 10+"px";
		}
		else {
			if(document.documentElement.clientWidth>0) {
				GetId("bi_curseur").style.left=10+event.x+document.documentElement.scrollLeft+"px";
				GetId("bi_curseur").style.top=10+event.y+document.documentElement.scrollTop+"px";
			} else {
				GetId("bi_curseur").style.left=10+event.x+document.body.scrollLeft+"px";
				GetId("bi_curseur").style.top=10+event.y+document.body.scrollTop+"px";
			}
		}
	}
}
function montre(text) {

		GetId("bi_curseur").style.visibility="visible";
		GetId("bi_curseur").innerHTML = text;
		bi_bulle=true;

}
function cache() {
	if(bi_bulle==true) {
		GetId("bi_curseur").style.visibility="hidden";
		bi_bulle=false;
	}
}
document.onmousemove=move;
