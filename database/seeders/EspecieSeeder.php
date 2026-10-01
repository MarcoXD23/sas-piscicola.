<?php

namespace Database\Seeders;

use App\Models\Especie;
use Illuminate\Database\Seeder;

class EspecieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $especies = [
            [
                'nombre_comun' => 'Mojarra Negra / Plateada',
                'nombre_cientifico' => 'Oreochromis niloticus',
                'familia' => 'Cichlidae',
                'clima' => 'cálido',
                'foto_url' => 'images/peces/mojarra_negra.jpg',
                'temperatura_min' => 26.0,
                'temperatura_max' => 30.0,
                'oxigeno_min_mg_l' => 4.00,
                'ph_min' => 6.50,
                'ph_max' => 8.50,
                'densidad_tierra_m2' => '4 a 6 peces/m²',
                'densidad_geomembrana_m3' => '30 a 50 peces/m³',
                'proteina_iniciacion' => '40% - 45%',
                'proteina_levante' => '32% - 34%',
                'proteina_engorde' => '24% - 28%',
                'meses_cosecha_promedio' => '5 a 6 meses',
                'peso_comercial_gramos' => '450 - 600 gramos',
                'rol_policultivo' => 'Especie principal de media agua y superficie. Excelente conversión alimenticia.',
                'guia_manejo_cultivo' => '1. Preparación del Estanque: Drenado total y secado al sol durante 5 a 7 días hasta que el fondo se agriete. Encalado con cal viva o apagada (50-100 g/m²) para desinfectar y neutralizar acidez. Llenado con filtro de malla fina para evitar ingreso de peces silvestres depredadores.
2. Aclimatación y Siembra: Colocar las bolsas de transporte flotando en el estanque durante 20-30 minutos para igualar temperaturas. Mezclar paulatinamente agua del estanque con la de la bolsa antes de liberar los alevinos en las horas frescas de la mañana (6:00 - 8:00 AM).
3. Manejo Nutricional: Administrar alimento en polvo y micropellet (45% proteína) en iniciación (3-4 raciones/día). Transición a peletizado de 32% en levante y 24-28% en engorde final ajustado por tabla de biomasa semanal.
4. Parámetros Críticos y Sanidad: Monitoreo diario de oxígeno disuelto a las 5:00 AM (activar aireadores si O2 < 3.5 mg/L). Chequeo semanal de pH y transparencia del disco Secchi (óptimo 30-40 cm).
5. Cosecha y Ayuno: Suspender alimento 24 a 36 horas antes de la cosecha para vaciado estomacal. Pesaje y choque térmico inmediato en agua con hielo a 0-4°C para garantizar inocuidad y frescura.',
            ],
            [
                'nombre_comun' => 'Mojarra Roja',
                'nombre_cientifico' => 'Oreochromis sp.',
                'familia' => 'Cichlidae',
                'clima' => 'cálido',
                'foto_url' => 'images/peces/mojarra_roja.jpg',
                'temperatura_min' => 26.0,
                'temperatura_max' => 30.0,
                'oxigeno_min_mg_l' => 4.00,
                'ph_min' => 6.80,
                'ph_max' => 8.20,
                'densidad_tierra_m2' => '3 a 5 peces/m²',
                'densidad_geomembrana_m3' => '25 a 40 peces/m³',
                'proteina_iniciacion' => '45%',
                'proteina_levante' => '32% - 34%',
                'proteina_engorde' => '24% - 30%',
                'meses_cosecha_promedio' => '5 a 6 meses',
                'peso_comercial_gramos' => '400 - 550 gramos',
                'rol_policultivo' => 'Especie estrella de consumo directo y restaurantes. Ocupa media agua con alta receptividad a alimento flotante.',
                'guia_manejo_cultivo' => '1. Preparación del Estanque: Desinfección rigurosa del fondo con cal dolomita o agrícola. Fertilización orgánica o inorgánica suave para estimular fitoplancton verde esmeralda.
2. Siembra y Aclimatación: Aclimatación térmica (diferencia no mayor a 1°C) y química (pH). Densidad de siembra recomendada de 3-5 peces/m² en estanques de tierra tradicionales con recambio de agua del 5-10% diario.
3. Alimentación por Etapas: Suministrar concentrado flotante extruido para evaluar el apetito y vigor en cada alimentación. Ajustar ración en días nublados o lluviosos donde disminuye el consumo.
4. Calidad del Agua: Muy sensible a concentraciones elevadas de amonio no ionizado (NH3) y nitritos. Mantener aireación mecánica activa durante la noche y madrugada.
5. Cosecha Comercial: Cosecha con redes de cerco lisas sin nudos para evitar desprendimiento de escamas que afecte la apariencia comercial dorada-rojiza.',
            ],
            [
                'nombre_comun' => 'Cachama Blanca',
                'nombre_cientifico' => 'Piaractus brachypomus',
                'familia' => 'Characidae',
                'clima' => 'cálido',
                'foto_url' => 'images/peces/cachama_blanca.jpg',
                'temperatura_min' => 26.0,
                'temperatura_max' => 30.0,
                'oxigeno_min_mg_l' => 4.00,
                'ph_min' => 6.00,
                'ph_max' => 8.00,
                'densidad_tierra_m2' => '1 a 2 peces/m²',
                'densidad_geomembrana_m3' => '8 a 15 peces/m³',
                'proteina_iniciacion' => '34% - 38%',
                'proteina_levante' => '28% - 32%',
                'proteina_engorde' => '24% - 28%',
                'meses_cosecha_promedio' => '6 meses',
                'peso_comercial_gramos' => '1.000 - 1.500 gramos (1.0 - 1.5 kg)',
                'rol_policultivo' => 'Excelente para policultivo con Tilapia y Bocachico. Aprovecha frutos, semillas, granos y no compite destructivamente.',
                'guia_manejo_cultivo' => '1. Resistencia y Biología: Especie amazónica/orinocense con extraordinaria rusticidad y resistencia a niveles bajos de oxígeno transitorio (hasta 2.5 mg/L).
2. Estanques Aptos: Se adapta óptimamente a estanques de tierra amplios con buena profundidad (1.2 a 1.8 metros).
3. Alimentación Omnívora: Excelente conversión alimenticia. Puede complementar su dieta con frutas caídas (guayaba, mango), hojas tiernas y granos cocidos, además del concentrado balanceado comercial.
4. Crecimiento Acelerado: Alcanza 1 kg en aproximadamente 5 a 6 meses bajo buena temperatura (>27°C).
5. Manejo en Cosecha: Manipulación rápida debido a su fuerza y dentadura molariforme. Despesque total o selectivo con red agallera de ojo adecuado.',
            ],
            [
                'nombre_comun' => 'Bocachico',
                'nombre_cientifico' => 'Prochilodus magdalenae',
                'familia' => 'Prochilodontidae',
                'clima' => 'cálido',
                'foto_url' => 'images/peces/bocachico.jpg',
                'temperatura_min' => 26.0,
                'temperatura_max' => 30.0,
                'oxigeno_min_mg_l' => 4.00,
                'ph_min' => 6.50,
                'ph_max' => 7.80,
                'densidad_tierra_m2' => '0.5 a 1 pez/m² (en policultivo)',
                'densidad_geomembrana_m3' => '5 a 8 peces/m³',
                'proteina_iniciacion' => '32%',
                'proteina_levante' => '26% - 28%',
                'proteina_engorde' => '20% - 24%',
                'meses_cosecha_promedio' => '8 a 10 meses',
                'peso_comercial_gramos' => '250 - 400 gramos',
                'rol_policultivo' => 'Limpiador de fondo detritívoro por excelencia. Consume restos orgánicos, perifiton y heces, manteniendo el suelo sano sin competir por concentrado.',
                'guia_manejo_cultivo' => '1. Importancia Ecológica y Policultivo: Especie nativa de Colombia emblemática de la cuenca Magdalena-Cauca. Su boca protráctil y hábito iliófago remueven suavemente el lodo y evitan la anoxia en el fondo del estanque.
2. Siembra Conjunta: Sembrar a razón de 1 bocachico por cada 3 a 4 mojarras o cachamas. No requiere concentrado especial flotante ya que aprovecha la biomasa perifítica y los desperdicios del peletizado.
3. Cuidados Sanitarios: Sensible al uso de químicos agresivos y pesticidas agrícolas arrastrados por lluvias. Mantener canales de desviación de aguas superficiales.
4. Cosecha y Gastronomía: Muy apetecido en la cultura culinaria colombiana (viudo de pescado, frito o sancocho). La cosecha se realiza en el despesque final del estanque.',
            ],
            [
                'nombre_comun' => 'Trucha Arcoíris',
                'nombre_cientifico' => 'Oncorhynchus mykiss',
                'familia' => 'Salmonidae',
                'clima' => 'frío',
                'foto_url' => 'images/peces/trucha_arcoiris.jpg',
                'temperatura_min' => 12.0,
                'temperatura_max' => 16.0,
                'oxigeno_min_mg_l' => 6.50,
                'ph_min' => 6.80,
                'ph_max' => 7.80,
                'densidad_tierra_m2' => '15 a 25 kg/m³ en raceways de flujo continuo',
                'densidad_geomembrana_m3' => '20 a 35 kg/m³',
                'proteina_iniciacion' => '45% - 50%',
                'proteina_levante' => '40% - 42%',
                'proteina_engorde' => '38% - 42%',
                'meses_cosecha_promedio' => '7 a 9 meses',
                'peso_comercial_gramos' => '250 - 350g (plato) o >500g (filete)',
                'rol_policultivo' => 'Monocultivo estricto en aguas frías de alta montaña. No compatible con especies de aguas cálidas.',
                'guia_manejo_cultivo' => '1. Requerimientos de Hábitat: Aguas de alta montaña (pisos térmicos fríos, páramo o subpáramo por encima de 1.800 msnm). Requiere aguas cristalinas, corrientes y con saturación de oxígeno superior al 80%.
2. Canales Raceways y Corriente: Se cultiva preferentemente en tanques de concreto o fibra rectangulares con corriente continua de recambio rápido (1 a 2 recambios completos por hora).
3. Dieta Alta en Proteína: Carnívora estricta. Necesita concentrados balanceados con alta inclusión de harina de pescado, aceites marinos y pigmentos astaxantina para coloración asalmonada.
4. Control de Densidad y Calibrado: Clasificación periódica por tallas cada 3 a 4 semanas para prevenir el canibalismo entre ejemplares grandes y pequeños.
5. Cosecha: En ayuno previo de 48 horas. Sacrificio inmediato por choque térmico en salmuera fría o hielo escamado para preservar textura firme.',
            ],
            [
                'nombre_comun' => 'Bagre Rayado',
                'nombre_cientifico' => 'Pseudoplatystoma fasciatum',
                'familia' => 'Pimelodidae',
                'clima' => 'cálido',
                'foto_url' => 'images/peces/bagre_rayado.jpg',
                'temperatura_min' => 26.0,
                'temperatura_max' => 30.0,
                'oxigeno_min_mg_l' => 4.00,
                'ph_min' => 6.50,
                'ph_max' => 7.50,
                'densidad_tierra_m2' => '0.5 a 1 pez/m²',
                'densidad_geomembrana_m3' => '3 a 6 peces/m³',
                'proteina_iniciacion' => '45% - 50%',
                'proteina_levante' => '40% - 45%',
                'proteina_engorde' => '36% - 40%',
                'meses_cosecha_promedio' => '9 a 12 meses',
                'peso_comercial_gramos' => '1.500 - 2.500 gramos (1.5 - 2.5 kg)',
                'rol_policultivo' => 'Depredador carnívoro de fondo. Utilizado para control de alevinaje no deseado en densidades bajas o monocultivo intensivo premium.',
                'guia_manejo_cultivo' => '1. Especie Insignia de Colombia: Pez emblemático de la cuenca del Río Magdalena, muy cotizado en la gastronomía nacional por su carne blanca, sin espinas intramusculares y alto rendimiento en posta.
2. Destete y Adaptación al Concentrado: Etapa crítica en alevinaje temprano, transición de alimento vivo (artemia/zooplancton) a pastas y micropellets secos de alta palatabilidad.
3. Hábitat en Estanque: Prefiere áreas profundas y sombreadas. Se recomienda instalar refugios de tubos de PVC o coberturas flotantes para reducir estrés por luz solar directa.
4. Alimentación Crepuscular: Es un pez con mayor actividad al atardecer y durante la noche; suministrar el 60-70% de la ración diaria al caer la tarde.
5. Cosecha Cuidadosa: Manejo con guantes y camillas húmedas para evitar pinchazos con sus espinas pectorales y dorsal.',
            ],
        ];

        foreach ($especies as $item) {
            Especie::updateOrCreate(
                ['nombre_cientifico' => $item['nombre_cientifico']],
                $item
            );
        }
    }
}
