<?php
/**
 * Post installation for UTB Rubrics grading method.
 *
 * @package    gradingform_utbrubrics
 * @copyright  2025 Isaac Sanchez, Santiago Orejuela, Luis Diaz, Maria Valentina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Post installation procedure - Complete Student Outcomes Installation
 *
 * @return bool true if success
 */
function xmldb_gradingform_utbrubrics_install() {
    global $DB;
    
    $time = time();
    
    // Define the 7 Student Outcomes with their complete data
    $student_outcomes_data = [
        'so1' => [
            'title_en' => 'Engineering Problem Analysis',
            'title_es' => 'Análisis de Problemas de Ingeniería',
            'description_en' => 'An ability to identify, formulate, and solve complex engineering problems by applying principles of engineering, science, and mathematics.',
            'description_es' => 'Una capacidad para identificar, formular y resolver problemas de ingeniería complejos aplicando principios de ingeniería, ciencia y matemáticas.',
            'indicators' => [
                'a' => [
                    'description_en' => 'Ability to identify complex engineering problems.',
                    'description_es' => 'Capacidad para identificar problemas complejos de ingeniería.'
                ],
                'b' => [
                    'description_en' => 'Ability to formulate complex engineering problems using principles of engineering, science, and mathematics based on available data.',
                    'description_es' => 'Capacidad para formular problemas de ingeniería complejos utilizando principios de ingeniería, ciencia y matemáticas basados ​​en los datos disponibles.'
                ],
                'c' => [
                    'description_en' => 'Ability to apply mathematical techniques to obtain a numerical resolution.',
                    'description_es' => 'Capacidad para aplicar técnicas matemáticas para obtener una resolución numérica.'
                ],
                'd' => [
                    'description_en' => 'Ability to analyze mathematical model responses of engineering problems.',
                    'description_es' => 'Capacidad para analizar las respuestas de modelos matemáticos de problemas de ingeniería.'
                ]
            ]
        ],
        'so2' => [
            'title_en' => 'Engineering Design',
            'title_es' => 'Diseño de Ingeniería',
            'description_en' => 'An ability to apply engineering design to produce solutions that meet specified needs with consideration of public health, safety, and welfare, as well as global, cultural, social, environmental, and economic factors.',
            'description_es' => 'Una capacidad de aplicar el diseño de ingeniería para producir soluciones que satisfagan necesidades específicas teniendo en cuenta la salud pública, la seguridad y el bienestar, así como factores globales, culturales, sociales, ambientales y económicos.',
            'indicators' => [
                'a' => [
                    'description_en' => 'Ability to define the problem and plan an effective design strategy.',
                    'description_es' => 'Capacidad para definir el problema y planificar una estrategia de diseño efectiva.'
                ],
                'b' => [
                    'description_en' => 'Ability to develop a suitable detailed design that considers alternative solutions.',
                    'description_es' => 'Capacidad para desarrollar un diseño detallado adecuado que considere soluciones alternativas.'
                ],
                'c' => [
                    'description_en' => 'Ability to consider realistic constraints with engineering standards and existing regulatory frameworks.',
                    'description_es' => 'Capacidad para considerar restricciones realistas con estándares de ingeniería y marcos regulatorios existentes.'
                ]
            ]
        ],
        'so3' => [
            'title_en' => 'Communication',
            'title_es' => 'Comunicación',
            'description_en' => 'An ability to communicate effectively with a range of audiences.',
            'description_es' => 'Una capacidad para comunicarse eficazmente con una variedad de públicos',
            'indicators' => [
                'a' => [
                    'description_en' => 'The student demonstrates the proper use of the writing style format according to the audience range.',
                    'description_es' => 'El estudiante demuestra el uso adecuado del formato de estilo de escritura de acuerdo al rango de audiencia.'
                ],
                'b' => [
                    'description_en' => 'The student demonstrates an adequate command of grammar and syntax.',
                    'description_es' => 'El estudiante demuestra un dominio adecuado de la gramática y la sintaxis.'
                ],
                'c' => [
                    'description_en' => 'The student demonstrates appropriate use of graphics in technical reports.',
                    'description_es' => 'El estudiante demuestra un uso apropiado de gráficos en informes técnicos.'
                ],
                'd' => [
                    'description_en' => 'The student demonstrates adequate body language and clarity in oral presentations.',
                    'description_es' => 'El estudiante demuestra un lenguaje corporal adecuado y claridad en las presentaciones orales.'
                ]
            ]
        ],
        'so4' => [
            'title_en' => 'Ethics and Professional Responsibility',
            'title_es' => 'Ética y Responsabilidad Profesional',
            'description_en' => 'An ability to recognize ethical and professional responsibilities in engineering situations and make informed judgments, which must consider the impact of engineering solutions in global, economic, environmental, and societal contexts.',
            'description_es' => 'Una capacidad para reconocer responsabilidades éticas y profesionales en situaciones de ingeniería y emitir juicios informados, que deben considerar el impacto de las soluciones de ingeniería en contextos globales, económicos, ambientales y sociales.',
            'indicators' => [
                'a' => [
                    'description_en' => 'Knowledge of the code of ethics.',
                    'description_es' => 'Conocimiento del código de ética.'
                ],
                'b' => [
                    'description_en' => 'Ability to recognize ethical responsibility in the profession.',
                    'description_es' => 'Capacidad para reconocer la responsabilidad ética en la profesión.'
                ],
                'c' => [
                    'description_en' => 'Analyzes the impact of engineering solutions, considering the global context, the economic, environmental and social context.',
                    'description_es' => 'Analiza el impacto de soluciones de ingeniería, considerando el contexto global, así como los contextos económicos, ambientales y sociales.'
                ]
            ]
        ],
        'so5' => [
            'title_en' => 'Teamwork',
            'title_es' => 'Trabajo en Equipo',
            'description_en' => 'An ability to function effectively on a team whose members together provide leadership, create a collaborative and inclusive environment, establish goals, plan tasks, and meet objectives.',
            'description_es' => 'Una capacidad para funcionar eficazmente en un equipo cuyos miembros juntos proporcionan liderazgo, crean un ambiente colaborativo e inclusivo, establecen metas, planifican tareas y cumplen objetivos.',
            'indicators' => [
                'a' => [
                    'description_en' => 'The team develops an efficient team-work strategy.',
                    'description_es' => 'El equipo desarrolla una estrategia de trabajo en equipo eficiente.'
                ],
                'b' => [
                    'description_en' => 'The team creates an inclusive and cooperative environment.',
                    'description_es' => 'El equipo crea un ambiente colaborativo e inclusivo.'
                ],
                'c' => [
                    'description_en' => 'Effective Planning of goals and tasks.',
                    'description_es' => 'Planificación efectiva de metas y tareas.'
                ],
                'd' => [
                    'description_en' => 'Objectives accomplishment.',
                    'description_es' => 'Cumplimiento de los objetivos.'
                ]
            ]
        ],
        'so6' => [
            'title_en' => 'Experimentation and Analysis',
            'title_es' => 'Experimentación y Análisis',
            'description_en' => 'An ability to develop and conduct appropriate experimentation, analyze and interpret data, and use engineering judgment to draw conclusions.',
            'description_es' => 'Una capacidad para desarrollar y llevar a cabo experimentos apropiados, analizar e interpretar datos y utilizar el criterio de ingeniería para sacar conclusiones.',
            'indicators' => [
                'a' => [
                    'description_en' => 'The student is able to formulate and develop experiments to draw engineering conclusions.',
                    'description_es' => 'El estudiante es capaz de formular y desarrollar experimentos para extraer conclusiones de ingeniería.'
                ],
                'b' => [
                    'description_en' => 'The student shows the ability to conduct an experiment.',
                    'description_es' => 'El estudiante muestra la capacidad de llevar a cabo un experimento.'
                ],
                'c' => [
                    'description_en' => 'The student is able to analyze and interpret data.',
                    'description_es' => 'El estudiante es capaz de analizar e interpretar datos.'
                ]
            ]
        ],
        'so7' => [
            'title_en' => 'Lifelong Learning',
            'title_es' => 'Aprendizaje Permanente',
            'description_en' => 'An ability to acquire and apply new knowledge as needed, using appropriate learning strategies.',
            'description_es' => 'Una capacidad para adquirir y aplicar nuevos conocimientos según sea necesario, utilizando estrategias de aprendizaje apropiadas.',
            'indicators' => [
                'a' => [
                    'description_en' => 'Independently identify, utilize and evaluate publicly available resources to, gather and synthesize information.',
                    'description_es' => 'Identificar, utilizar y evaluar de forma independiente los recursos disponibles públicamente para recopilar y sintetizar información.'
                ],
                'b' => [
                    'description_en' => 'Demonstrate ability to actively and independently pursue new learning opportunities to successfully learn a new skill.',
                    'description_es' => 'Demostrar la capacidad de buscar activamente y de forma independiente nuevas oportunidades de aprendizaje para aprender con éxito una nueva habilidad.'
                ],
                'c' => [
                    'description_en' => 'Apply New Knowledge for solving problems.',
                    'description_es' => 'Aplica nuevos conocimientos para resolver problemas.'
                ]
            ]
        ]
    ];
    
    // Performance levels specific for each Student Outcome and Indicator
    $performance_levels_by_so = [
        'so1' => [
            'a' => [ // Ability to identify complex engineering problems
                'Excellent' => [
                    'description_en' => 'Identifies completely variables and parameters of a complex engineering problem.',
                    'description_es' => 'Identifica completamente las variables y parámetros de un problema complejo de ingeniería.'
                ],
                'Good' => [
                    'description_en' => 'Identifies the relevant variables and parameters of a complex engineering problem.',
                    'description_es' => 'Identifica las variables y parámetros relevantes de un problema complejo de ingeniería.'
                ],
                'Fair' => [
                    'description_en' => 'Identifies some variables and parameters of a complex engineering problem.',
                    'description_es' => 'Identifica algunas variables y parámetros de un problema complejo de ingeniería.'
                ],
                'Inadequate' => [
                    'description_en' => 'Identify neither variables nor parameters of a complex engineering problem.',
                    'description_es' => 'No identifica ni variables ni parámetros de un problema complejo de ingeniería.'
                ]
            ],
            'b' => [ // Ability to formulate complex engineering problems
                'Excellent' => [
                    'description_en' => 'Establishes all required equations to formulate a complex engineering problem based on available information.',
                    'description_es' => 'Establece todas las ecuaciones requeridas para formular un problema complejo de ingeniería basado en la información disponible.'
                ],
                'Good' => [
                    'description_en' => 'Establishes enough equations to formulate a complex engineering problem based on available information.',
                    'description_es' => 'Establece suficientes ecuaciones para formular un problema complejo de ingeniería basado en la información disponible.'
                ],
                'Fair' => [
                    'description_en' => 'Establishes some equations to formulate a complex engineering problem based on available information.',
                    'description_es' => 'Establece algunas ecuaciones para formular un problema complejo de ingeniería basado en la información disponible.'
                ],
                'Inadequate' => [
                    'description_en' => 'Does not use appropriate equations, as a consequence cannot formulate a complex engineering problem based on available information.',
                    'description_es' => 'No utiliza ecuaciones apropiadas, como consecuencia no puede formular un problema complejo de ingeniería basado en la información disponible.'
                ]
            ],
            'c' => [ // Ability to apply mathematical techniques
                'Excellent' => [
                    'description_en' => 'Applies advanced mathematical techniques with complete accuracy and obtains precise numerical solutions with proper validation.',
                    'description_es' => 'Aplica técnicas matemáticas avanzadas con completa precisión y obtiene soluciones numéricas precisas con validación apropiada.'
                ],
                'Good' => [
                    'description_en' => 'Applies appropriate mathematical techniques effectively and obtains accurate numerical solutions with minor errors.',
                    'description_es' => 'Aplica técnicas matemáticas apropiadas efectivamente y obtiene soluciones numéricas precisas con errores menores.'
                ],
                'Fair' => [
                    'description_en' => 'Applies basic mathematical techniques but may have some computational errors or choose less optimal methods.',
                    'description_es' => 'Aplica técnicas matemáticas básicas pero puede tener algunos errores computacionales o elegir métodos menos óptimos.'
                ],
                'Inadequate' => [
                    'description_en' => 'Does not apply methods of mathematical resolution of complex engineering problems.',
                    'description_es' => 'No aplica métodos de resolución matemática de problemas complejos de ingeniería.'
                ]
            ],
            'd' => [ // Ability to analyze mathematical model responses
                'Excellent' => [
                    'description_en' => 'Analyses appropriately mathematical model responses in different scenarios of complex engineering problems.',
                    'description_es' => 'Analiza adecuadamente las respuestas del modelo matemático en diferentes escenarios de problemas complejos de ingeniería.'
                ],
                'Good' => [
                    'description_en' => 'Analyses mathematical model responses in specific scenarios of complex engineering problems.',
                    'description_es' => 'Analiza las respuestas del modelo matemático en escenarios específicos de problemas complejos de ingeniería.'
                ],
                'Fair' => [
                    'description_en' => 'Analyses inadequately mathematical model responses of complex engineering problems.',
                    'description_es' => 'Analiza inadecuadamente las respuestas del modelo matemático de problemas complejos de ingeniería.'
                ],
                'Inadequate' => [
                    'description_en' => 'Does not analyze mathematical model responses in complex engineering problems.',
                    'description_es' => 'No analiza las respuestas del modelo matemático en problemas complejos de ingeniería.'
                ]
            ]
        ],
        'so2' => [
            'a' => [ // Ability to define the problem and plan design strategy
                'Excellent' => [
                    'description_en' => 'The problem to be solved is clearly stated. Objectives are complete, specific, and concise. Customer needs and applicable realistic constraints are correctly identified and transformed into design requirements. A workable and detailed design strategy is developed.',
                    'description_es' => 'El problema a resolver está claramente planteado. Los objetivos son completos, específicos y concisos. Las necesidades del cliente y las restricciones realistas aplicables se identifican correctamente y se transforman en requisitos de diseño. Se desarrolla una estrategia de diseño viable y detallada.'
                ],
                'Good' => [
                    'description_en' => 'The problem to be solved is described but: 1) there are minor omissions or vague details, 2) objectives are incomplete, 3) objectives are badly transformed into design requirements, or 4) some important realistic constraints are neglected.',
                    'description_es' => 'El problema a resolver está descrito pero: 1) hay omisiones menores o detalles vagos, 2) los objetivos son incompletos, 3) los objetivos están mal transformados en requisitos de diseño, o 4) se han descuidado algunas restricciones realistas importantes.'
                ],
                'Fair' => [
                    'description_en' => 'The problem is vaguely defined, and an attempt is made to develop a workable design strategy, but it is either incomplete or unclear. Objectives are unclear and/or poorly defined.',
                    'description_es' => 'El problema está vagamente definido y se hace un intento de desarrollar una estrategia de diseño viable, pero es incompleta o poco clara. Los objetivos son poco claros y/o están mal definidos.'
                ], 
                'Inadequate' => [
                    'description_en' => 'No mention is made to the problem to be solved and no design strategy is proposed, haphazard approach.',
                    'description_es' => 'No se menciona el problema a resolver y no se propone ninguna estrategia de diseño, enfoque desorganizado.'
                ]
            ],
            'b' => [ // Ability to develop detailed design considering alternatives
                'Excellent' => [
                    'description_en' => 'The student can present a detailed engineering design of a viable solution that considers multiple alternatives and selects the best one, responding to the existing constraints and customer needs.',
                    'description_es' => 'El estudiante puede presentar un diseño de ingeniería detallado de una solución viable que considere múltiples alternativas y seleccione la mejor, respondiendo a las restricciones existentes y las necesidades del cliente.'
                ],
                'Good' => [
                    'description_en' => 'The student can present a detailed engineering design, but the solution is not viable, the selected alternative is not the best, or some existing constraints and customer needs are not reached.',
                    'description_es' => 'El estudiante puede presentar un diseño de ingeniería detallado, pero la solución no es viable, la alternativa seleccionada no es la mejor o no se han alcanzado algunas restricciones existentes y necesidades del cliente.'
                ],
                'Fair' => [
                    'description_en' => 'The student presents a basic engineering design, the solution is not viable, no alternatives are evaluated, and most existing constraints and customer needs are not reached.',
                    'description_es' => 'El estudiante presenta un diseño de ingeniería básico, la solución no es viable, no se evalúan alternativas y no se alcanzan la mayoría de las restricciones existentes y las necesidades del cliente.'
                ],
                'Inadequate' => [
                    'description_en' => 'The presented design lacks detailed information and it does not consider any other alternative.',
                    'description_es' => 'El diseño presentado carece de información detallada y no considera ninguna otra alternativa.'
                ]
            ],
            'c' => [ // Ability to consider realistic constraints and standards
                'Excellent' => [
                    'description_en' => 'Developed specifications include economic, safety, environmental, and other realistic constraints, and selects appropriate standards which are fully incorporated in the design.',
                    'description_es' => 'Las especificaciones desarrolladas incluyen restricciones económicas, de seguridad, ambientales y otras restricciones realistas, y selecciona estándares apropiados que se incorporan completamente en el diseño.'
                ],
                'Good' => [
                    'description_en' => 'Developed specifications include only minor or superficial consideration of economic, safety, and environmental constraints. Selects appropriate standards, but they are not fully incorporated in the design.',
                    'description_es' => 'Las especificaciones desarrolladas incluyen solo consideraciones menores o superficiales de las restricciones económicas, de seguridad y ambientales. Selecciona estándares apropiados, pero no se incorporan completamente en el diseño.'
                ],
                'Fair' => [
                    'description_en' => 'Developed specifications lack consideration of economic, safety, and environmental constraints. The regulatory framework required is incomplete and is not incorporated in the design.',
                    'description_es' => 'Las especificaciones desarrolladas carecen de consideración de las restricciones económicas, de seguridad y ambientales. El marco regulatorio requerido es incompleto y no se incorpora en el diseño.'
                ],
                'Inadequate' => [
                    'description_en' => 'Developed specifications violate some realistic constraints. Selects inappropriate standards.',
                    'description_es' => 'Las especificaciones desarrolladas violan algunas restricciones realistas. Selecciona estándares inapropiados.'
                ]
            ]
        ],
        'so3' => [
            'a' => [ // Proper use of writing style format according to audience
                'Excellent' => [
                    'description_en' => 'Use a writing style appropriate to the audience range, with excellent structure and order in the text.',
                    'description_es' => 'Usa un estilo de escritura apropiado para el rango de audiencia, con excelente estructura y orden en el texto.'
                ],
                'Good' => [
                    'description_en' => 'Use a writing style appropriate to the audience range, with good structure and order in the text.',
                    'description_es' => 'Usa un estilo de escritura apropiado para el rango de audiencia, con buena estructura y orden en el texto.'
                ],
                'Fair' => [
                    'description_en' => 'Use a writing style that is not appropriate for the audience range, with little order in the text.',
                    'description_es' => 'Usa un estilo de escritura que no es apropiado para el rango de audiencia, con poco orden en el texto.'
                ],
                'Inadequate' => [
                    'description_en' => 'Use a style of writing that is not appropriate for the audience range, with text without structure or order.',
                    'description_es' => 'Usa un estilo de escritura que no es apropiado para el rango de audiencia, con texto sin estructura ni orden.'
                ]
            ],
            'b' => [ // Adequate command of grammar and syntax
                'Excellent' => [
                    'description_en' => 'Demonstrates flawless command of grammar and syntax with sophisticated sentence structure and precise word choice.',
                    'description_es' => 'Demuestra dominio impecable de gramática y sintaxis con estructura de oraciones sofisticada y elección precisa de palabras.'
                ],
                'Good' => [
                    'description_en' => 'Syntax, grammar, and spelling are fully correct.',
                    'description_es' => 'La sintaxis, gramática y ortografía son completamente correctas.'
                ],
                'Fair' => [
                    'description_en' => 'The syntax, grammar, and spelling present some errors.',
                    'description_es' => 'La sintaxis, gramática y ortografía presentan algunos errores.'
                ],
                'Inadequate' => [
                    'description_en' => 'Syntax, grammar, and spelling frequently fail.',
                    'description_es' => 'La sintaxis, gramática y ortografía fallan con frecuencia.'
                ]
            ],
            'c' => [ // Appropriate use of graphics in technical reports
                'Excellent' => [
                    'description_en' => 'Use graphics with relevant information in a creative, organized and clear manner.',
                    'description_es' => 'Usa gráficos con información relevante de manera creativa, organizada y clara.'
                ],
                'Good' => [
                    'description_en' => 'Use graphics with relevant information, good use of colors and text size.',
                    'description_es' => 'Usa gráficos con información relevante, buen uso de colores y tamaño de texto.'
                ],
                'Fair' => [
                    'description_en' => 'Use graphics with relevant information with shortcomings in organization as well as colors, size, and shape of texts.',
                    'description_es' => 'Usa gráficos con información relevante con deficiencias en la organización, así como en los colores, tamaño y forma de los textos.'
                ],
                'Inadequate' => [
                    'description_en' => 'Use graphics with little or no relevant and unorganized information. Inappropriate use of colors, size, and shape of texts.',
                    'description_es' => 'Usa gráficos con poca o ninguna información relevante y desorganizada. Uso inapropiado de colores, tamaño y forma de los textos.'
                ]
            ],
            'd' => [ // Adequate body language and clarity in oral presentations
                'Excellent' => [
                    'description_en' => 'Demonstrates easiness and fluency in speaking, combined with fluent and dynamic body language, gestures, posture, and appropriate intonation.',
                    'description_es' => 'Demuestra facilidad y fluidez al hablar, combinada con un lenguaje corporal fluido y dinámico, gestos, postura e intonación apropiada.'
                ],
                'Good' => [
                    'description_en' => 'Demonstrates fluency in speaking, with appropriate body language and intonation.',
                    'description_es' => 'Demuestra fluidez al hablar, con lenguaje corporal e intonación apropiados.'
                ],
                'Fair' => [
                    'description_en' => 'Demonstrates fluency in speech, with some deficiencies in body language and intonation.',
                    'description_es' => 'Demuestra fluidez en el habla, con algunas deficiencias en el lenguaje corporal e intonación.'
                ],
                'Inadequate' => [
                    'description_en' => 'Demonstrates little fluency in speech, intonation, and a monotonous tone of voice.',
                    'description_es' => 'Demuestra poca fluidez en el habla, la entonación y un tono de voz monótono.'
                ]
            ]
        ],
        'so4' => [
            'a' => [ // Knowledge of the code of ethics
                'Excellent' => [
                    'description_en' => "Shows proficiency of the profession's code of ethics.",
                    'description_es' => 'Muestra dominio del código de ética de la profesión.'
                ],
                'Good' => [
                    'description_en' => "Shows an adequate command of the profession's code of ethics.",
                    'description_es' => 'Muestra un dominio adecuado del código de ética de la profesión.'
                ],
                'Fair' => [
                    'description_en' => "Shows a basic command of the profession's code of ethics.",
                    'description_es' => 'Muestra un dominio básico del código de ética de la profesión.'
                ],
                'Inadequate' => [
                    'description_en' => "Does not know the profession's code of ethics.",
                    'description_es' => 'No conoce el código de ética de la profesión.'
                ]
            ],
            'b' => [ // Ability to recognize ethical responsibility
                'Excellent' => [
                    'description_en' => 'Recognizes responsibility on different dilemmas or ethical situations that may be presented in the profession on an autonomous way.',
                    'description_es' => 'Reconoce la responsabilidad en diferentes dilemas o situaciones éticas que pueden presentarse en la profesión de manera autónoma.'
                ],
                'Good' => [
                    'description_en' => "Shows an adequate understanding of the profession's responsibilities on determined situations and can recognize ethical responsibility autonomously.",
                    'description_es' => 'Muestra un entendimiento adecuado de las responsabilidades de la profesión en situaciones determinadas y puede reconocer la responsabilidad ética de manera autónoma.'
                ],
                'Fair' => [
                    'description_en' => "Shows a basic understanding of the profession's responsibilities but needs assistance to identify them.",
                    'description_es' => 'Muestra un entendimiento básico de las responsabilidades de la profesión pero necesita ayuda para identificarlas.'
                ],
                'Inadequate' => [
                    'description_en' => 'Does not recognize the responsibility for different dilemmas or ethical situations that can be presented in the profession.',
                    'description_es' => 'No reconoce la responsabilidad en diferentes dilemas o situaciones éticas que pueden presentarse en la profesión.'
                ]
            ],
            'c' => [ // Analyzes impact considering global, economic, environmental and social contexts
                'Excellent' => [
                    'description_en' => 'Analyzes all global, economic, environmental and social impacts involved on the solution of engineering problems and can make informed judgments.',
                    'description_es' => 'Analiza todos los impactos globales, económicos, ambientales y sociales involucrados en la solución de problemas de ingeniería y puede emitir juicios informados.'
                ],
                'Good' => [
                    'description_en' => 'Analyses many of the global, economic, environmental and social impacts involved in the solution of engineering problems.',
                    'description_es' => 'Analiza muchos de los impactos globales, económicos, ambientales y sociales involucrados en la solución de problemas de ingeniería.'
                ],
                'Fair' => [
                    'description_en' => 'Analyzes some of the global, economic, environmental and social impacts involved on the solution of engineering problems.',
                    'description_es' => 'Analiza algunos de los impactos globales, económicos, ambientales y sociales involucrados en la solución de problemas de ingeniería.'
                ],
                'Inadequate' => [
                    'description_en' => 'Does not analyze the global, economic, environmental and social impacts involved in the solution of engineering problems.',
                    'description_es' => 'No analiza los impactos globales, económicos, ambientales y sociales involucrados en la solución de problemas de ingeniería.'
                ]
            ]
        ],
        'so5' => [
            'a' => [ // Team develops efficient team-work strategy
                'Excellent' => [
                    'description_en' => 'The student participates and energizes all team roles and functions.',
                    'description_es' => 'El estudiante participa y energiza todos los roles y funciones del equipo.'
                ],
                'Good' => [
                    'description_en' => 'The student participates actively in his required roles and functions within the team.',
                    'description_es' => 'El estudiante participa activamente en sus roles y funciones requeridos dentro del equipo.'
                ],
                'Fair' => [
                    'description_en' => 'The student follows her required roles and functions in the team but lacks proactivity.',
                    'description_es' => 'El estudiante cumple con sus roles y funciones requeridos en el equipo pero carece de proactividad.'
                ],
                'Inadequate' => [
                    'description_en' => 'The student does not adjust to her required roles and functions within the team.',
                    'description_es' => 'El estudiante no se ajusta a sus roles y funciones requeridos dentro del equipo.'
                ]
            ],
            'b' => [ // Team creates inclusive and cooperative environment
                'Excellent' => [
                    'description_en' => 'Mutual respect among team members allows everyone to give the best individual results. There is cooperation among members. Conflicts are solved with dialog and commitment.',
                    'description_es' => 'El respeto mutuo entre los miembros del equipo permite que todos den los mejores resultados individuales. Hay cooperación entre los miembros. Los conflictos se resuelven con diálogo y compromiso.'
                ],
                'Good' => [
                    'description_en' => 'In general, there is a positive and motivating relationship between team members. A majority of members cooperate and accomplish goals with notorious cooperation.',
                    'description_es' => 'En general, hay una relación positiva y motivadora entre los miembros del equipo. La mayoría de los miembros cooperan y logran objetivos con notable cooperación.'
                ],
                'Fair' => [
                    'description_en' => 'Some team members feel excluded and do not participate effectively. Nonetheless, the team fulfills all its goals and objectives although with an unequal work distribution.',
                    'description_es' => 'Algunos miembros del equipo se sienten excluidos y no participan de manera efectiva. No obstante, el equipo cumple con todos sus objetivos aunque con una distribución desigual del trabajo.'
                ],
                'Inadequate' => [
                    'description_en' => "The team neglects the usage of one or several of its members' abilities and/or capacities.",
                    'description_es' => 'El equipo descuida el uso de una o varias de las habilidades y/o capacidades de sus miembros.'
                ]
            ],
            'c' => [ // Effective planning of goals and tasks
                'Excellent' => [
                    'description_en' => 'The team has an effective planning strategy plus adequate methodologies that are followed consistently to attain the desired intermediate and final goals.',
                    'description_es' => 'El equipo tiene una estrategia de planificación efectiva más metodologías adecuadas que se siguen de manera consistente para alcanzar las metas intermedias y finales deseadas.'
                ],
                'Good' => [
                    'description_en' => 'Planning is well done and methodologies to follow are clear, nonetheless, some team members do not apply it in an orderly manner.',
                    'description_es' => 'La planificación está bien hecha y las metodologías a seguir son claras, sin embargo, algunos miembros del equipo no la aplican de manera ordenada.'
                ],
                'Fair' => [
                    'description_en' => 'There is good planning, but many team members do not accomplish their required tasks and goals.',
                    'description_es' => 'Hay una buena planificación, pero muchos miembros del equipo no cumplen con sus tareas y objetivos requeridos.'
                ],
                'Inadequate' => [
                    'description_en' => 'There is no effective plan for a solution to the proposed tasks.',
                    'description_es' => 'No hay un plan efectivo para una solución a las tareas propuestas.'
                ]
            ],
            'd' => [ // Objectives accomplishment
                'Excellent' => [
                    'description_en' => 'Every team member fulfills all his duties and meets the requested individual tasks and goals. Therefore the team accomplishes all objectives, tasks and proposed goals.',
                    'description_es' => 'Cada miembro del equipo cumple con todos sus deberes y cumple con las tareas y objetivos individuales solicitados. Por lo tanto, el equipo cumple con todos los objetivos, tareas y metas propuestas.'
                ],
                'Good' => [
                    'description_en' => 'Most objectives and goals are met effectively and in due time by team members. Required team tasks are met.',
                    'description_es' => 'La mayoría de los objetivos y metas se cumplen de manera efectiva y en el tiempo debido por los miembros del equipo. Se cumplen las tareas requeridas del equipo.'
                ],
                'Fair' => [
                    'description_en' => 'The main goals are met. Some objectives are lacking time, resources or dedication from team members.',
                    'description_es' => 'Se cumplen las metas principales. A algunos objetivos les faltan tiempo, recursos o dedicación por parte de los miembros del equipo.'
                ],
                'Inadequate' => [
                    'description_en' => 'Goals and required tasks are not met.',
                    'description_es' => 'No se cumplen las metas y tareas requeridas.'
                ]
            ]
        ],
        'so6' => [
            'a' => [ // Ability to formulate and develop experiments
                'Excellent' => [
                    'description_en' => 'Presents an experiment plan that takes into account alternatives and constraints, and includes an efficient procedure for data collection.',
                    'description_es' => 'Presenta un plan de experimento que toma en cuenta alternativas y restricciones, e incluye un procedimiento eficiente para la recolección de datos.'
                ],
                'Good' => [
                    'description_en' => 'Presents an experiment plan including a procedure for data collection, which can be used to draw engineering conclusions.',
                    'description_es' => 'Presenta un plan de experimento que incluye un procedimiento para la recolección de datos, que puede ser utilizado para extraer conclusiones de ingeniería.'
                ],
                'Fair' => [
                    'description_en' => 'Presents an experiment plan that may result in data that does not allow engineering conclusions to be drawn.',
                    'description_es' => 'Presenta un plan de experimento que puede resultar en datos que no permiten extraer conclusiones de ingeniería.'
                ],
                'Inadequate' => [
                    'description_en' => 'The student does not present an experiment plan that can lead to engineering conclusions.',
                    'description_es' => 'El estudiante no presenta un plan de experimento que pueda llevar a conclusiones de ingeniería.'
                ]
            ],
            'b' => [ // Ability to conduct an experiment
                'Excellent' => [
                    'description_en' => 'The data acquisition scheme is fully and carefully described, and all relevant data was collected.',
                    'description_es' => 'El esquema de adquisición de datos está completamente y cuidadosamente descrito, y se recopilaron todos los datos relevantes.'
                ],
                'Good' => [
                    'description_en' => 'Sufficient data was collected and the characteristics of the instrument used are shown.',
                    'description_es' => 'Se recopilaron datos suficientes y se muestran las características del instrumento utilizado.'
                ],
                'Fair' => [
                    'description_en' => 'Data was collected, but the characteristics of the instruments used are not presented or analyzed.',
                    'description_es' => 'Se recopilaron datos, pero no se presentan ni analizan las características de los instrumentos utilizados.'
                ],
                'Inadequate' => [
                    'description_en' => 'Data collection is incomplete or inadequate.',
                    'description_es' => 'La recopilación de datos es incompleta o inadecuada.'
                ]
            ],
            'c' => [ // Ability to analyze and interpret data
                'Excellent' => [
                    'description_en' => 'The analysis and interpretation of the data is correct. Are included very detailed engineering conclusions, correct and well supported.',
                    'description_es' => 'El análisis e interpretación de los datos es correcto. Se incluyen conclusiones de ingeniería muy detalladas, correctas y bien fundamentadas.'
                ],
                'Good' => [
                    'description_en' => 'The analysis and interpretation of the data is well done and includes engineering conclusions.',
                    'description_es' => 'El análisis y la interpretación de los datos están bien realizados e incluyen conclusiones de ingeniería.'
                ],
                'Fair' => [
                    'description_en' => 'The analysis and interpretation of the data is generally correct but contains some weaknesses or errors that do not totally invalidate it.',
                    'description_es' => 'El análisis y la interpretación de los datos es generalmente correcto pero contiene algunas debilidades o errores que no lo invalidan totalmente.'
                ],
                'Inadequate' => [
                    'description_en' => 'The analysis and interpretation of the data is inadequate, invalid, incomplete or contains significant errors.',
                    'description_es' => 'El análisis y la interpretación de los datos es inadecuado, inválido, incompleto o contiene errores significativos.'
                ]
            ]
        ],
        'so7' => [
            'a' => [ // Independently identify, utilize and evaluate resources
                'Excellent' => [
                    'description_en' => 'Able to consult several sources of information available to the discipline, is able to synthesize the results and evaluate the quality of the information obtained.',
                    'description_es' => 'Puede consultar varias fuentes de información disponibles para la disciplina, es capaz de sintetizar los resultados y evaluar la calidad de la información obtenida.'
                ],
                'Good' => [
                    'description_en' => 'Able to consult several sources of information of the discipline, shows capacity for information synthesis but does not evaluate the information found.',
                    'description_es' => 'Puede consultar varias fuentes de información de la disciplina, muestra capacidad para la síntesis de información pero no evalúa la información encontrada.'
                ],
                'Fair' => [
                    'description_en' => 'Able to consult several sources of information related to the discipline, however, there are difficulties to synthesize the information and evaluate it.',
                    'description_es' => 'Puede consultar varias fuentes de información relacionadas con la disciplina, sin embargo, hay dificultades para sintetizar la información y evaluarla.'
                ],
                'Inadequate' => [
                    'description_en' => 'Does not recognize different sources of information available from its discipline that provide updated knowledge.',
                    'description_es' => 'No reconoce las diferentes fuentes de información disponibles de su disciplina que proporcionan conocimientos actualizados.'
                ]
            ],
            'b' => [ // Demonstrate ability to pursue new learning opportunities
                'Excellent' => [
                    'description_en' => 'Able to identify opportunities for improving a set of skills or knowledge, and demonstrates that they can be acquired through an explicit plan.',
                    'description_es' => 'Puede identificar oportunidades para mejorar un conjunto de habilidades o conocimientos, y demuestra que pueden adquirirse a través de un plan explícito.'
                ],
                'Good' => [
                    'description_en' => 'Able to identify opportunities to improve a set of skills or knowledge, and set out a plan to achieve them. However, does not demonstrate that it has been put into practice.',
                    'description_es' => 'Puede identificar oportunidades para mejorar un conjunto de habilidades o conocimientos, y establece un plan para lograrlas. Sin embargo, no demuestra que se haya puesto en práctica.'
                ],
                'Fair' => [
                    'description_en' => 'Able to identify opportunities to improve a set of skills or knowledge but does not have a plan to achieve them.',
                    'description_es' => 'Puede identificar oportunidades para mejorar un conjunto de habilidades o conocimientos pero no tiene un plan para lograrlas.'
                ],
                'Inadequate' => [
                    'description_en' => 'Does not recognize the need of getting new knowledge or skills.',
                    'description_es' => 'No reconoce la necesidad de adquirir nuevos conocimientos o habilidades.'
                ]
            ],
            'c' => [ // Apply new knowledge for solving problems
                'Excellent' => [
                    'description_en' => 'Able to apply the results of the consulted material to solve engineering problems related to the discipline.',
                    'description_es' => 'Puede aplicar los resultados del material consultado para resolver problemas de ingeniería relacionados con la disciplina.'
                ],
                'Good' => [
                    'description_en' => 'Able to satisfactorily solve engineering challenges although the use of new knowledge is not clear.',
                    'description_es' => 'Puede resolver satisfactoriamente desafíos de ingeniería aunque el uso de nuevos conocimientos no es claro.'
                ],
                'Fair' => [
                    'description_en' => 'Able to learn new analysis tools. However, the application of the new knowledge to solve an engineering problem is only presented occasionally.',
                    'description_es' => 'Puede aprender nuevas herramientas de análisis. Sin embargo, la aplicación del nuevo conocimiento para resolver un problema de ingeniería solo se presenta ocasionalmente.'
                ],
                'Inadequate' => [
                    'description_en' => 'Not able to apply new tools for solving problems that has not previously solved.',
                    'description_es' => 'No es capaz de aplicar nuevas herramientas para resolver problemas que no ha resuelto anteriormente.'
                ]
            ]
        ]
    ];
    
    // Standard performance level titles and score ranges
    $standard_levels = [
        'Excellent' => ['title_es' => 'Excelente', 'minscore' => 4.50, 'maxscore' => 5.00, 'sortorder' => 4],
        'Good' => ['title_es' => 'Bueno', 'minscore' => 3.50, 'maxscore' => 4.49, 'sortorder' => 3],
        'Fair' => ['title_es' => 'Regular', 'minscore' => 3.00, 'maxscore' => 3.49, 'sortorder' => 2],
        'Inadequate' => ['title_es' => 'Inadecuado', 'minscore' => 0.00, 'maxscore' => 2.99, 'sortorder' => 1]
    ];
    
    try {
        // Begin transaction for data integrity
        $transaction = $DB->start_delegated_transaction();
        
        $sortorder = 1;
        foreach ($student_outcomes_data as $so_key => $so_data) {
            // Insert Student Outcome
            $so_record = new stdClass();
            $so_record->so_number = $so_key;
            $so_record->title_en = $so_data['title_en'];
            $so_record->title_es = $so_data['title_es'];
            $so_record->description_en = $so_data['description_en'];
            $so_record->description_es = $so_data['description_es'];
            $so_record->sortorder = $sortorder++;
            $so_record->timecreated = $time;
            $so_record->timemodified = $time;
            
            $so_id = $DB->insert_record('gradingform_utb_outcomes', $so_record);
            
            foreach ($so_data['indicators'] as $indicator_letter => $indicator_data) {
                // Insert Indicator
                $indicator_record = new stdClass();
                $indicator_record->student_outcome_id = $so_id;
                $indicator_record->indicator_letter = $indicator_letter;
                $indicator_record->description_en = $indicator_data['description_en'];
                $indicator_record->description_es = $indicator_data['description_es'];
                $indicator_record->timecreated = $time;
                $indicator_record->timemodified = $time;
                
                $indicator_id = $DB->insert_record('gradingform_utb_indicators', $indicator_record);
                
                // Insert Performance Levels specific to this indicator
                $performance_levels_for_indicator = $performance_levels_by_so[$so_key][$indicator_letter];
                
                foreach ($standard_levels as $level_name_en => $level_config) {
                    // Get specific descriptions for this SO and indicator
                    $level_descriptions = $performance_levels_for_indicator[$level_name_en];
                    
                    // Insert Performance Level in gradingform_utb_lvl
                    $lvl_record = new stdClass();
                    $lvl_record->indicator_id = $indicator_id;
                    $lvl_record->title_en = $level_name_en;
                    $lvl_record->title_es = $level_config['title_es'];
                    $lvl_record->description_en = $level_descriptions['description_en'];
                    $lvl_record->description_es = $level_descriptions['description_es'];
                    $lvl_record->minscore = $level_config['minscore'];
                    $lvl_record->maxscore = $level_config['maxscore'];
                    $lvl_record->sortorder = $level_config['sortorder'];
                    $lvl_record->timecreated = $time;
                    $lvl_record->timemodified = $time;
                    
                    $DB->insert_record('gradingform_utb_lvl', $lvl_record);
                }
            }
        }
        
        // Commit transaction
        $transaction->allow_commit();
        return true;
        
    } catch (Exception $e) {
        // Rollback on error
        $transaction->rollback($e);
        return false;
    }
}