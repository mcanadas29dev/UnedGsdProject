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

function buscarUsuarios(){
    const query = event.target.value;

    if (query.length < 2) return;
    const btninput = document.getElementById('btn-query-user');
    console.log(query.target.value);
    //btninput.click();
    /*
    fetch(`/admin/buscar-usuarios?query=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            console.log('Usuarios encontrados:', data);
            // Aquí renderizas resultados, por ejemplo en una tabla o dropdown
        })
        .catch(err => {
            console.error('Error al buscar usuarios:', err);
        });
    */
}

document.addEventListener("DOMContentLoaded", function () {
    console.log("Empezamos con Javascript");
    const input = document.getElementById('query-user');
    
    if (input) {
        input.addEventListener('keyup', buscarUsuarios); // o 'keyup'
    }
    /*
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
    */
});


