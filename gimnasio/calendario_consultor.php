<?php
session_start();

// CONEXIÓN A LA BASE DE DATOS
$servername = "localhost";     
$username = "root";         
$password = "";             
$database = "gimnasio";   

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'consultor') {
    header("Location: login.php");
    exit();
}

// Obtener datos reales de entrenadores y clases
$entrenadores_data = [];
$result = $conn->query("
    SELECT e.id_entrenador, e.nombre, e.especialidad, 
           c.id_clase, c.nombre_clase, c.horario, c.capacidad
    FROM entrenadores e
    LEFT JOIN clases c ON e.id_entrenador = c.id_entrenador
    WHERE c.id_clase IS NOT NULL
    ORDER BY e.nombre, c.nombre_clase
");

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $entrenador_nombre = $row['nombre'];
        if (!isset($entrenadores_data[$entrenador_nombre])) {
            $entrenadores_data[$entrenador_nombre] = [];
        }
        
        if ($row['id_clase']) {
            // Convertir horario a días y hora
            $horario = $row['horario'];
            $days = [];
            $time = '08:00'; // Por defecto
            
            // Mapeo de días
            if (strpos($horario, 'Lun') !== false) $days[] = 1;
            if (strpos($horario, 'Mar') !== false) $days[] = 2;
            if (strpos($horario, 'Mié') !== false) $days[] = 3;
            if (strpos($horario, 'Jue') !== false) $days[] = 4;
            if (strpos($horario, 'Vie') !== false) $days[] = 5;
            if (strpos($horario, 'Sáb') !== false) $days[] = 6;
            if (strpos($horario, 'Dom') !== false) $days[] = 0;
            
            // Extraer hora
            if (preg_match('/(\d{1,2}:\d{2})\s*(AM|PM)/i', $horario, $matches)) {
                $time = $matches[1];
                // Convertir a formato 24 horas si es PM
                if (strtoupper($matches[2]) === 'PM' && $time !== '12:00') {
                    list($h, $m) = explode(':', $time);
                    $time = ($h + 12) . ':' . $m;
                }
            }
            
            $entrenadores_data[$entrenador_nombre][] = [
                'clase' => $row['nombre_clase'],
                'days' => $days,
                'time' => $time,
                'duration' => 60, // Por defecto
                'capacity' => $row['capacidad'],
                'id_clase' => $row['id_clase'], // ID real de la clase
                'id_entrenador' => $row['id_entrenador'] // ID real del entrenador
            ];
        }
    }
}

// Convertir a JSON para usar en JavaScript
$entrenadores_json = json_encode($entrenadores_data);
?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Power STAY — Agenda Semanal</title>
  <style>
    .user-info a {
      color: white;
      text-decoration: none;
      margin: 0 10px;
    }
    .user-info a:hover {
      text-decoration: underline;
    }
    :root{--primary:#002b5b;--accent:#004a93;--bg:#f5f6fa;--card:#fff}
    *{box-sizing:border-box}
    body{font-family:Segoe UI,Arial; background:var(--bg); margin:0; color:#222}
    header{background:var(--primary); color:#fff; padding:12px 16px; text-align:center; font-weight:700}
    .wrap{max-width:1200px; margin:14px auto; padding:10px}
    .grid{display:grid; grid-template-columns:300px 1fr; gap:14px}
    .panel{background:var(--card); border-radius:10px; padding:12px; box-shadow:0 3px 10px rgba(0,0,0,0.06)}
    h2{color:var(--primary); margin:4px 0 10px}
    label{display:block; font-weight:600; color:#03407a; margin-top:8px}
    select,input[type=date],button{width:100%; padding:8px; border-radius:6px; border:1px solid #d7e3f6}
    .btn{background:var(--primary); color:#fff; border:none; padding:8px; margin-top:8px; cursor:pointer; font-weight:700}
    .btn.secondary{background:#777}
    .btn.success{background:#28a745;}
    .btn.danger{background:#dc3545;}

    /* AGENDA */
    .agenda-header{display:flex; justify-content:space-between; align-items:center; margin-bottom:8px}
    .week-nav{display:flex; gap:8px; align-items:center}
    .week-days{display:grid; grid-template-columns:repeat(7,1fr); gap:6px}
    .day-cell{background:linear-gradient(180deg,#f8fbff,#fff); padding:8px; border-radius:8px; text-align:center; font-weight:700}

    .agenda{display:grid; grid-template-columns:80px 1fr; gap:8px}
    .hours{background:transparent}
    .hours .hour{height:48px; padding:6px; color:#555; border-bottom:1px dashed #eef4fb}

    .week-grid{position:relative; overflow:auto; background:var(--card); border-radius:8px; padding:8px}
    .columns{display:grid; grid-template-columns:repeat(7,1fr); gap:6px}
    .column{min-height:960px; position:relative; background:transparent}
    .slot{position:absolute; left:0; right:0; margin:0 2px; padding:6px 8px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.06); font-size:13px; color:#06243a}

    .pill-title{font-weight:700; font-size:13px}
    .pill-sub{font-size:12px; color:#063d66}
    .status-full{background:#ffd6d6; border-left:4px solid #ff6b6b}
    .status-mid{background:#fff8c6; border-left:4px solid #ffd54f}
    .status-free{background:#d8ffd8; border-left:4px solid #4caf50}

    /* Modal */
    .modal-backdrop{position:fixed; inset:0; background:rgba(0,0,0,0.36); display:none; align-items:center; justify-content:center; z-index:1000;}
    .modal{background:#fff; width:420px; border-radius:10px; padding:16px; max-height:90vh; overflow-y:auto;}
    .modal h3{margin:0 0 8px}
    .modal .row{display:flex; justify-content:space-between; gap:8px; margin:8px 0}

    /* reports */
    .report-list{display:flex; gap:8px}
    .stat{flex:1; background:#f8fbff; padding:8px; border-radius:8px; text-align:center}

    /* Consultas section */
    .consulta-section{margin-top:16px; padding:12px; background:#f0f8ff; border-radius:8px;}
    .consulta-section h3{margin:0 0 8px 0; color:#002b5b;}
    .resultados-consulta{margin-top:12px; max-height:200px; overflow-y:auto; display:none; border:1px solid #d7e3f6; border-radius:4px; padding:8px; background:white;}

    @media(max-width:980px){.grid{grid-template-columns:1fr} .columns{overflow:auto} .column{min-height:700px}}
  </style>
</head>
<body>
    <header>
    <h1>Panel de Consultor - Gimnasio Power STAY</h1>
    <div class="user-info">
      <strong>Consultor:</strong> <?php echo htmlspecialchars($_SESSION['usuario']); ?> | 
      <a href="logout.php">Cerrar Sesión</a>
    </div>
  </header>

  <div class="wrap">
    <div class="grid">
      <!-- LEFT PANEL -->
      <div class="panel">
        <h2>Filtros & Acciones</h2>
        <label>Entrenador</label>
        <select id="filterEntrenador">
            <option value="">-- Todos --</option>
            <?php
            $entrenadores_result = $conn->query("SELECT id_entrenador, nombre FROM entrenadores ORDER BY nombre");
            if ($entrenadores_result && $entrenadores_result->num_rows > 0) {
                while($entrenador = $entrenadores_result->fetch_assoc()) {
                    echo '<option value="' . $entrenador['id_entrenador'] . '">' . htmlspecialchars($entrenador['nombre']) . '</option>';
                }
            }
            ?>
        </select>

        <label>Seleccionar fecha</label>
        <input type="date" id="filterFecha">

        <button id="btnAplicar" class="btn">Aplicar filtros</button>
        <button id="btnLimpiar" class="btn secondary" style="margin-top:6px">Limpiar</button>

        <div class="consulta-section">
            <h3>Consultas</h3>
            
            <label for="consultarCliente">Consultar por Cliente ID</label>
            <input type="number" id="consultarCliente" placeholder="ID del cliente" style="width: 100%; padding: 6px; border-radius: 4px; border: 1px solid #d7e3f6;">
            
            <label for="consultarFecha">Consultar por Fecha</label>
            <input type="date" id="consultarFecha" style="width: 100%; padding: 6px; border-radius: 4px; border: 1px solid #d7e3f6;">
            
            <button id="btnConsultarReservas" class="btn success" style="margin-top: 8px;">Consultar Reservas</button>
            
            <div id="resultadoConsulta" class="resultados-consulta">
                <h4 style="margin: 0 0 8px 0;">Resultados:</h4>
                <div id="listaReservas"></div>
            </div>
        </div>

        <h3 style="margin-top:12px">Entrenadores</h3>
        <div id="listCoaches" style="display:flex;flex-direction:column;gap:8px; max-height:220px; overflow:auto">
            <?php
            $entrenadores_list = $conn->query("SELECT id_entrenador, nombre FROM entrenadores ORDER BY nombre");
            if ($entrenadores_list && $entrenadores_list->num_rows > 0) {
                while($entrenador = $entrenadores_list->fetch_assoc()) {
                    echo '<div style="padding:8px; border:1px solid #e6eefc; border-radius:6px; cursor:pointer;" onclick="filtrarPorEntrenador(' . $entrenador['id_entrenador'] . ')">' . htmlspecialchars($entrenador['nombre']) . '</div>';
                }
            }
            ?>
        </div>

        <h3 style="margin-top:12px">Reportes (mes)</h3>
        <div class="report-list">
          <div class="stat"><div id="rTotal">0</div><small>Total clases</small></div>
          <div class="stat"><div id="rRes">0</div><small>Reservas</small></div>
          <div class="stat"><div id="rAsis">0</div><small>Asistencias</small></div>
        </div>

      </div>

      <!-- RIGHT PANEL: AGENDA -->
      <div class="panel">
        <div class="agenda-header">
          <div style="display:flex;align-items:center;gap:10px">
            <div class="week-nav">
              <button id="prevWeek" class="btn" style="width:42px">&lt;</button>
              <button id="nextWeek" class="btn" style="width:42px">&gt;</button>
            </div>
            <h3 id="weekLabel">Semana</h3>
          </div>
          <div style="display:flex;gap:8px;align-items:center">
            <small style="color:#666">Semana actual por defecto</small>
          </div>
        </div>

        <div class="week-days" id="weekDays"></div>

        <div class="agenda" style="margin-top:12px">
          <div class="hours" id="hoursCol"></div>
          <div class="week-grid">
            <div class="columns" id="columns"></div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Modal -->
  <div class="modal-backdrop" id="modalBackdrop">
    <div class="modal" role="dialog" aria-modal="true">
      <h3 id="modalTitle">Detalle clase</h3>
      <div id="modalBody">
        <p><strong>Clase:</strong> <span id="mClase"></span></p>
        <p><strong>Entrenador:</strong> <span id="mEntrenador"></span></p>
        <p><strong>Horario:</strong> <span id="mHorario"></span> — <span id="mDuracion"></span> min</p>
        <p><strong>Cupos:</strong> <span id="mCupos"></span> — <span id="mOcupacion"></span> reservados</p>
        <p><strong>ID Clase:</strong> <span id="mIdClase"></span></p>
      </div>
      <div style="margin-top:8px; display:flex; gap:8px; justify-content:flex-end">
        <button id="btnToggleReserve" class="btn" style="display:none">Reservar</button>
        <button id="btnMarkAsis" class="btn secondary" style="display:none">Marcar asistencia</button>
        <button id="btnClose" class="btn secondary">Cerrar</button>
      </div>
    </div>
  </div>

  <script>
    /***** CONFIG *****/
    const START_HOUR = 6; const END_HOUR = 21; // 6:00 - 21:00
    const CLIENTE_ACTUAL = 1; // simulado
    let IS_ADMIN = false; 
    let IS_CONSULTOR = true; 

    /***** DATOS DESDE PHP *****/
    const entrenadores = <?php echo $entrenadores_json; ?>;

    /***** ESTADO *****/
    let viewStartDate = startOfWeek(new Date()); // lunes
    const columnsEl = document.getElementById('columns');
    const weekLabel = document.getElementById('weekLabel');

    // DOM refs modal
    const modalBg = document.getElementById('modalBackdrop');
    const mClase = document.getElementById('mClase');
    const mEntrenador = document.getElementById('mEntrenador');
    const mHorario = document.getElementById('mHorario');
    const mDuracion = document.getElementById('mDuracion');
    const mCupos = document.getElementById('mCupos');
    const mOcupacion = document.getElementById('mOcupacion');
    const mIdClase = document.getElementById('mIdClase');
    const btnToggleReserve = document.getElementById('btnToggleReserve');
    const btnMarkAsis = document.getElementById('btnMarkAsis');

    let activeClass = null; // objeto seleccionado

    /***** INIT UI *****/
    init();

    function init(){
      renderWeek(); 
      attachControls(); 
      updateStats();
    }

    function filtrarPorEntrenador(idEntrenador) {
        document.getElementById('filterEntrenador').value = idEntrenador;
        applyFilters();
    }

    function attachControls(){
      document.getElementById('prevWeek').addEventListener('click', ()=>{ viewStartDate.setDate(viewStartDate.getDate()-7); renderWeek(); updateStats(); });
      document.getElementById('nextWeek').addEventListener('click', ()=>{ viewStartDate.setDate(viewStartDate.getDate()+7); renderWeek(); updateStats(); });
      document.getElementById('btnAplicar').addEventListener('click', ()=>{ applyFilters(); });
      document.getElementById('btnLimpiar').addEventListener('click', ()=>{ document.getElementById('filterEntrenador').value=''; document.getElementById('filterFecha').value=''; renderWeek(); updateStats(); });
      document.getElementById('btnClose').addEventListener('click', closeModal);
      document.getElementById('btnConsultarReservas').addEventListener('click', consultarReservas);
      btnToggleReserve.addEventListener('click', handleToggleReserve);
      btnMarkAsis.addEventListener('click', handleMarkAsistencia);
    }

    function consultarReservas() {
        const clienteId = document.getElementById('consultarCliente').value;
        const fecha = document.getElementById('consultarFecha').value;
        
        if (!clienteId && !fecha) {
            alert('Ingresa al menos un criterio de búsqueda (Cliente ID o Fecha)');
            return;
        }

        let url = 'api.php?action=reservacionesPorFecha';
        if (fecha) url += `&fecha=${fecha}`;
        if (clienteId) url += `&cliente=${clienteId}`;

        fetch(url)
            .then(r => r.json())
            .then(data => {
                mostrarResultadosConsulta(data.list || []);
            })
            .catch(err => {
                console.error('Error en consulta:', err);
                alert('Error al consultar reservas');
            });
    }

    function mostrarResultadosConsulta(reservas) {
        const contenedor = document.getElementById('listaReservas');
        const resultadoDiv = document.getElementById('resultadoConsulta');
        
        if (reservas.length === 0) {
            contenedor.innerHTML = '<p style="color: #666; font-style: italic;">No se encontraron reservas</p>';
        } else {
            contenedor.innerHTML = reservas.map(reserva => `
                <div style="padding: 8px; margin-bottom: 6px; background: white; border-radius: 4px; border-left: 4px solid #007bff;">
                    <strong>Cliente ${reserva.id_cliente} - ${reserva.nombre_cliente || 'N/A'}</strong><br>
                    ${reserva.nombre_entrenador || 'N/A'} - ${reserva.nombre_clase || reserva.clase || 'N/A'}<br>
                    <small>Fecha: ${reserva.fecha_reservacion || reserva.fecha} | Estado: ${reserva.estado || 'N/A'} | Asistencia: ${reserva.asistencia ? 'Sí' : 'No'}</small>
                </div>
            `).join('');
        }
        
        resultadoDiv.style.display = 'block';
    }

    function applyFilters(){ 
        const date = document.getElementById('filterFecha').value; 
        if(date){ 
            viewStartDate = startOfWeek(new Date(date)); 
        } 
        renderWeek(); 
        updateStats(); 
    }

    function renderWeek(){
      // header days
      const weekDaysEl = document.getElementById('weekDays'); 
      weekDaysEl.innerHTML='';
      const cols = document.getElementById('columns'); 
      cols.innerHTML='';
      const start = new Date(viewStartDate);
      weekLabel.textContent = `Semana del ${start.toLocaleDateString('es-ES')}`;

      // hours column
      const hoursCol = document.getElementById('hoursCol'); 
      hoursCol.innerHTML='';
      for(let h=START_HOUR; h<=END_HOUR; h++){ 
          const div = document.createElement('div'); 
          div.className='hour'; 
          div.textContent = formatHour(h); 
          hoursCol.appendChild(div);
      }

      for(let i=0;i<7;i++){
        const d = new Date(start); 
        d.setDate(start.getDate()+i);
        const dayName = d.toLocaleDateString('es-ES',{weekday:'short'});
        const dayNum = d.getDate();
        const dayCell = document.createElement('div'); 
        dayCell.className='day-cell'; 
        dayCell.innerHTML = `<div style=font-size:12px>${dayName}</div><div style=font-size:16px>${dayNum}</div>`; 
        weekDaysEl.appendChild(dayCell);

        // create column
        const col = document.createElement('div'); 
        col.className='column'; 
        col.dataset.date = toISODate(d);
        cols.appendChild(col);
      }

      // place classes
      placeClassesInWeek();
    }

    function placeClassesInWeek(){
      const cols = document.querySelectorAll('.column');
      cols.forEach(c=> c.innerHTML='');
      const filterEntr = document.getElementById('filterEntrenador').value;

      // iterate day columns
      cols.forEach((col,i)=>{
        const dateStr = col.dataset.date; 
        const d = new Date(dateStr); 
        const weekday = d.getDay();
        
        // gather classes that occur this weekday
        const classes = [];
        Object.keys(entrenadores).forEach(ent=>{
          entrenadores[ent].forEach(cl=>{ 
            if(cl.days.includes(weekday)) 
              classes.push(Object.assign({},cl,{entrenador:ent})); 
          });
        });
        
        // filter by entrenador select
        const filtered = classes.filter(c=> {
          if (!filterEntr) return true;
          // Buscar el entrenador por ID
          const entrenadorSelect = document.getElementById('filterEntrenador');
          const selectedOption = entrenadorSelect.options[entrenadorSelect.selectedIndex];
          return selectedOption.text === c.entrenador;
        });

        // for each class create slot
        filtered.forEach(cl=>{
          const top = calcTopInPx(cl.time);
          const height = (cl.duration/60)*48;
          const slot = document.createElement('div');
          slot.className = 'slot status-free';
          slot.style.top = `${top}px`;
          slot.style.height = `${height}px`;

          slot.innerHTML = `<div class='pill-title'>${cl.clase}</div><div class='pill-sub'>${cl.time} • ${cl.entrenador}</div>`;
          slot.onclick = ()=> openModal(cl, dateStr);
          col.appendChild(slot);
        });
      });
    }

    /***** MODAL & RESERVAS *****/
    function openModal(claseObj, dateStr){
        activeClass = { ...claseObj, date: dateStr };
        mClase.textContent = claseObj.clase;
        mEntrenador.textContent = claseObj.entrenador;
        mHorario.textContent = `${claseObj.time} — ${dateStr}`;
        mDuracion.textContent = claseObj.duration;
        mCupos.textContent = claseObj.capacity;
        mIdClase.textContent = claseObj.id_clase;

        // OCULTAR BOTONES DE ACCIÓN PARA CONSULTOR
        btnToggleReserve.style.display = 'none';
        btnMarkAsis.style.display = 'none';

        // fetch ocupacion real desde API usando ID real de la clase
        fetch(`api.php?action=reservacionesPorFecha&fecha=${dateStr}&clase=${claseObj.id_clase}`)
            .then(r=>r.json())
            .then(data=>{
                const occ = data.count || 0; 
                mOcupacion.textContent = occ;
                updateModalButtons(occ);
                modalBg.style.display = 'flex';
            })
            .catch(err=>{ 
                console.error(err); 
                mOcupacion.textContent='?'; 
                modalBg.style.display='flex'; 
                updateModalButtons(0); 
            });
    }

    function updateModalButtons(occCount){
        // Para consultor, no mostramos botones de acción
        btnToggleReserve.style.display = 'none';
        btnMarkAsis.style.display = 'none';
    }

    function closeModal(){ 
        modalBg.style.display='none'; 
        activeClass=null; 
    }

    function handleToggleReserve(){
        // No disponible para consultor
        alert('Acción no disponible para consultor');
    }

    function handleMarkAsistencia(){
        // No disponible para consultor
        alert('Acción no disponible para consultor');
    }

    /***** HELPERS *****/
    function formatHour(h){ 
        const am = h<12? 'am':'pm'; 
        const hh = ((h+11)%12+1); 
        return `${hh}:00 ${am}`; 
    }
    
    function startOfWeek(d){ 
        const nd = new Date(d); 
        const day = nd.getDay(); 
        const diff = (day+6)%7; 
        nd.setDate(nd.getDate()-diff); 
        nd.setHours(0,0,0,0); 
        return nd; 
    }
    
    function toISODate(d){ 
        const y=d.getFullYear(); 
        const m=('0'+(d.getMonth()+1)).slice(-2); 
        const day=('0'+d.getDate()).slice(-2); 
        return `${y}-${m}-${day}`; 
    }
    
    function parseDateTime(dateStr, timeStr){ 
        const [hh,mm]=timeStr.split(':').map(Number); 
        const d=new Date(dateStr); 
        d.setHours(hh,mm,0,0); 
        return d; 
    }

    function calcTopInPx(timeStr){ 
        const [hh,mm]=timeStr.split(':').map(Number); 
        const minutes = (hh - START_HOUR)*60 + mm; 
        return (minutes/60)*48; 
    }

    /***** API INTEGRATION *****/
    function updateStats(){
        const start = viewStartDate; 
        const month = start.getMonth()+1; 
        const year = start.getFullYear();
        fetch(`api.php?action=statsMes&month=${month}&year=${year}`)
            .then(r=>r.json())
            .then(j=>{
                document.getElementById('rTotal').textContent = j.totalClases || 0; 
                document.getElementById('rRes').textContent = j.reservas || 0; 
                document.getElementById('rAsis').textContent = j.asistencias || 0; 
            })
            .catch(err => {
                console.error('Error al cargar estadísticas:', err);
            });
    }

    // Actualizar ocupación periódicamente
    setInterval(()=>{ 
        if(document.querySelectorAll('.column').length) {
            updateStats();
        } 
    }, 30000);

  </script>
</body>
</html>
<?php
$conn->close();
?>