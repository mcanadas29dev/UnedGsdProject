function activar(){
        
    const list = document.getElementById("fooddata-list");
    if (!list) return;

    fetch("https://api.nal.usda.gov/fdc/v1/foods/list?api_key=DEMO_KEY&pageSize=4")
        .then(res => res.json())
        .then(data => {
            list.innerHTML = "";
            if (!Array.isArray(data) || data.length === 0) {
                list.innerHTML = "<p>No hay alimentos disponibles.</p>";
                return;
            }
            
            padre.classList("list-unstyled mb-0");
            data.forEach(food => {
                const li = document.createElement("li");
                li.innerHTML = `<strong>${food.description}</strong> (ID: ${food.fdcId})`;
                list.appendChild(li);

            });
        })
        .catch(error => {
            console.error("Error cargando datos desde FoodData Central:", error);
            list.innerHTML = "<li>Error al cargar alimentos.</li>";
        });
}

function mostrarAside(ele){
     ele.style.color = 'red';
}

document.addEventListener("DOMContentLoaded", function () {
    // Usa querySelectorAll para obtener todos los elementos con clase 'abc'
    //let elementoDiv1 = document.getElementById("abc1");
    
    let elementosDiv = document.querySelectorAll('.abc');

    // Recorre todos los elementos
    elementosDiv.forEach(function (elemento) {
        elemento.addEventListener('mouseover', function () {
            
            elemento.classList.add('hover-activo');
           
        });

        elemento.addEventListener('mouseout', function () {
            elemento.classList.remove('hover-activo');
            
        });
    });
});


