<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';

requireLogin();

$mode = isset($_GET['setup']) ? 'setup' : (isset($_GET['exam']) ? 'exam' : (isset($_GET['practice']) ? 'practice' : 'setup'));
$subjects = available_subjects();

function generate_subject_questions($subject, $count) {
    $out = [];
    if ($subject === 'Mathematics') {
        for ($i=0; $i<$count; $i++) {
            $a = rand(2, 19);
            $b = rand(2, 19);
            $op = ['+','-','×','÷'][rand(0,3)];
            if ($op === '+') { $ans = $a + $b; $opts = [$ans, $ans+rand(1,3), $ans-rand(1,3), $ans+rand(4,6)]; }
            elseif ($op === '-') { $ans = $a - $b; $opts = [$ans, $ans+rand(1,3), $ans-rand(1,3), $ans+rand(4,6)]; }
            elseif ($op === '×') { $ans = $a * $b; $opts = [$ans, $ans+$a, $ans-$b, $ans+$b]; }
            else { $ans = (int)floor($a / max(1,$b)); $opts = [$ans, $ans+1, $ans-1, $ans+2]; }
            shuffle($opts);
            $ansIdx = array_search($ans, $opts, true);
            if ($ansIdx === false) { $ansIdx = 0; $opts[0] = $ans; }
            $out[] = ['q'=>"Solve: {$a} {$op} {$b} =", 'opts'=>$opts, 'ans'=>$ansIdx];
        }
        return $out;
    }
    if ($subject === 'English') {
        $pairs = [['rapid','quick'],['begin','start'],['joy','happiness'],['smart','clever'],['sad','unhappy'],['angry','furious'],['help','assist'],['end','finish'],['big','large'],['small','tiny']];
        $phrases = ['Choose the synonym of', 'Select the synonym of', 'Pick the synonym of', 'Identify the synonym of'];
        $seen = [];
        for ($i=0; $i<$count; $i++) {
            $p = $pairs[$i % count($pairs)];
            $distractor = $pairs[($i + 1) % count($pairs)][0];
            $opts = [$distractor, $p[1], $p[1].'ly', 'not '.$p[1]];
            shuffle($opts);
            $ansIdx = array_search($p[1], $opts, true);
            $prefix = $phrases[$i % count($phrases)];
            $qText = $prefix." \"{$p[0]}\"";
            if (isset($seen[$qText])) {
                $qText = $qText.' — '.$prefix;
            }
            $seen[$qText] = true;
            $out[] = ['q'=>$qText, 'opts'=>$opts, 'ans'=>$ansIdx];
        }
        return $out;
    }
    $samplesAll = sampleQuestions();
    $samples = $samplesAll[$subject] ?? [];
    if (count($samples) > 0) {
        $pool = $samples;
        shuffle($pool);
        $phrases = ['Choose the correct answer:', 'Select the right option:', 'Pick the correct choice:', 'Which is correct?'];
        $seen = [];
        for ($i=0; $i<$count; $i++) {
            $base = $pool[$i % count($pool)];
            $opts = $base['opts'];
            $correct = $base['opts'][$base['ans']];
            shuffle($opts);
            $ansIdx = array_search($correct, $opts, true);
            $prefix = $phrases[$i % count($phrases)];
            $qText = $base['q'];
            if ($i >= count($pool)) {
                $qText = $prefix.' '.$qText;
            }
            if (isset($seen[$qText])) {
                $qText = $qText.' — '.$prefix;
            }
            $seen[$qText] = true;
            $out[] = ['q'=>$qText, 'opts'=>$opts, 'ans'=>$ansIdx];
        }
        return $out;
    }
    for ($i=0; $i<$count; $i++) {
        $opts = ['Option A','Option B','Option C','Option D'];
        shuffle($opts);
        $out[] = ['q'=>"Sample question for {$subject}", 'opts'=>$opts, 'ans'=>0];
    }
    return $out;
}

function generate_ai_subject_questions($subject, $count) {
    $bank = get_jamb_bank_questions($subject, $count);
    $out = [];
    if (is_array($bank) && count($bank) > 0) {
        foreach ($bank as $q) {
            $out[] = $q;
            if (count($out) >= $count) break;
        }
    }
    if (count($out) < $count) {
        $topics = jamb_syllabus_topics()[$subject] ?? ["Core {$subject}"];
        $topic = $topics[array_rand($topics)];
        $aiQs = ai_generate_practice_questions($topic, $count, $subject);
        if (is_array($aiQs) && count($aiQs) > 0) {
            foreach ($aiQs as $q) {
                $opts = $q['options'] ?? [];
                if (count($opts) !== 4) continue;
                $ansLetter = strtoupper($q['answer'] ?? 'A');
                $map = ['A'=>0,'B'=>1,'C'=>2,'D'=>3];
                $ansIdx = $map[$ansLetter] ?? 0;
                $out[] = ['q'=>$q['question'] ?? "Question on {$topic}", 'opts'=>$opts, 'ans'=>$ansIdx];
                if (count($out) >= $count) break;
            }
        }
    }
    if (count($out) < $count) {
        $fallback = generate_subject_questions($subject, $count - count($out));
        $out = array_merge($out, $fallback);
    }
    $seen = [];
    $uniq = [];
    foreach ($out as $q) {
        if (isset($seen[$q['q']])) continue;
        $seen[$q['q']] = true;
        $uniq[] = $q;
    }
    if (count($uniq) < $count) {
        $more = generate_subject_questions($subject, $count - count($uniq));
        foreach ($more as $q) {
            if (isset($seen[$q['q']])) continue;
            $seen[$q['q']] = true;
            $uniq[] = $q;
            if (count($uniq) >= $count) break;
        }
    }
    return $uniq;
}

if ($mode === 'setup' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        setFlash('error', 'Security token mismatch. Please try again.');
        redirect(SITE_URL . '/jamb-cbt.php?setup=1');
    }
    $chosen = $_POST['subjects'] ?? [];
    if (!in_array('English', $chosen)) $chosen[] = 'English';
    $chosen = array_values(array_intersect($subjects, $chosen));
    if (count($chosen) !== 4) {
        setFlash('error', 'Select English and exactly 3 other subjects');
        redirect(SITE_URL . '/jamb-cbt.php?setup=1');
    }
    $_SESSION['cbt_subjects'] = $chosen;
    if (isset($_SESSION['cbt_started_at'])) unset($_SESSION['cbt_started_at']);
    redirect(SITE_URL . '/jamb-cbt.php?exam=1');
}

function sampleQuestions() {
    return [
        'English' => [
            ['q' => 'Choose the synonym of "rapid"', 'opts' => ['slow','quick','late','dull'], 'ans' => 1],
            ['q' => 'Which is a vowel sound?', 'opts' => ['b','t','a','k'], 'ans' => 2],
            ['q' => 'Comprehension tests understanding of', 'opts' => ['numbers','texts','maps','music'], 'ans' => 1],
            ['q' => 'Lexis relates to', 'opts' => ['words','images','numbers','notes'], 'ans' => 0],
            ['q' => 'Summary writing requires', 'opts' => ['details','brevity','figures','tables'], 'ans' => 1],
            ['q' => 'Choose the synonym of "Candid"', 'opts' => ['Deceptive','Frank','Secretive','Guarded'], 'ans' => 1],
            ['q' => 'Choose the antonym of "Arrogant"', 'opts' => ['Proud','Humble','Loud','Rude'], 'ans' => 1],
            ['q' => 'The sound /θ/ is found in', 'opts' => ['There','Then','Thin','That'], 'ans' => 2],
            ['q' => '"To let the cat out of the bag" means', 'opts' => ['To release a pet','To reveal a secret','To make noise','To steal'], 'ans' => 1],
            ['q' => 'Choose the correct spelling', 'opts' => ['Priviledge','Privilege','Privelege','Previlage'], 'ans' => 1],
            ['q' => 'The plural of "Crisis" is', 'opts' => ['Crises','Crisises','Crisus','Crisi'], 'ans' => 0],
            ['q' => 'Identify the noun phrase in: "The tall man is here"', 'opts' => ['is here','The tall man','tall','man is'], 'ans' => 1],
            ['q' => 'Choose the option with stress on the first syllable', 'opts' => ['Export (Verb)','Export (Noun)','Comply','Begin'], 'ans' => 1],
            ['q' => '"He is good ___ football"', 'opts' => ['in','at','on','with'], 'ans' => 1],
            ['q' => 'The register of medicine includes', 'opts' => ['Stanza','Diagnosis','Debit','Plaintiff'], 'ans' => 1]
        ],
        'Mathematics' => [
            ['q' => 'Solve: 2x=10, x=', 'opts' => ['2','5','10','20'], 'ans' => 1],
            ['q' => 'Triangle angles sum equals', 'opts' => ['90°','180°','270°','360°'], 'ans' => 1],
            ['q' => 'Mean of 2,4,6', 'opts' => ['3','4','5','6'], 'ans' => 2],
            ['q' => 'Simplify: 3(2+1)', 'opts' => ['3','6','9','12'], 'ans' => 2],
            ['q' => 'sin(90°)=', 'opts' => ['0','1','-1','0.5'], 'ans' => 1],
            ['q' => 'Solve for x: x² - 5x + 6 = 0', 'opts' => ['2, 3','-2, -3','1, 6','-1, -6'], 'ans' => 0],
            ['q' => 'The gradient of the line y = 3x - 2 is', 'opts' => ['-2','2','3','-3'], 'ans' => 2],
            ['q' => 'Calculate the area of a circle with radius 7cm (π=22/7)', 'opts' => ['154cm²','44cm²','22cm²','144cm²'], 'ans' => 0],
            ['q' => '2⁵ × 2³ =', 'opts' => ['2⁸','2¹⁵','2²','2³'], 'ans' => 0],
            ['q' => 'If P = {1,2,3} and Q = {3,4,5}, find P ∩ Q', 'opts' => ['{1,2}','{3}','{4,5}','{1,2,3,4,5}'], 'ans' => 1],
            ['q' => 'Convert 101₂ to base 10', 'opts' => ['2','3','5','4'], 'ans' => 2],
            ['q' => 'Differentiation of x² is', 'opts' => ['x','2x','x/2','2'], 'ans' => 1],
            ['q' => 'Integration is the reverse of', 'opts' => ['Multiplication','Differentiation','Addition','Subtraction'], 'ans' => 1],
            ['q' => 'Probability of getting a head in a coin toss', 'opts' => ['1/2','1/4','1','0'], 'ans' => 0],
            ['q' => 'Find the median of 2, 5, 1, 4, 3', 'opts' => ['1','2','3','4'], 'ans' => 2]
        ],
        'Physics' => [
            ['q' => 'SI unit of force', 'opts' => ['joule','watt','newton','pascal'], 'ans' => 2],
            ['q' => 'Heat transfer by waves', 'opts' => ['conduction','convection','radiation','fusion'], 'ans' => 2],
            ['q' => 'Speed of light in vacuum ~', 'opts' => ['3e6','3e8','3e10','3e12'], 'ans' => 1],
            ['q' => 'Ohm’s law: V=', 'opts' => ['IR','I/R','R/I','VI'], 'ans' => 0],
            ['q' => 'Lens focusing relates to', 'opts' => ['mechanics','optics','nuclear','quantum'], 'ans' => 1],
            ['q' => 'Scalar quantities have', 'opts' => ['Magnitude only','Direction only','Magnitude and Direction','None'], 'ans' => 0],
            ['q' => 'Acceleration is the rate of change of', 'opts' => ['Distance','Displacement','Velocity','Speed'], 'ans' => 2],
            ['q' => 'The unit of Power is', 'opts' => ['Joule','Watt','Newton','Volt'], 'ans' => 1],
            ['q' => 'Sound waves cannot travel through', 'opts' => ['Solids','Liquids','Gases','Vacuum'], 'ans' => 3],
            ['q' => 'A convex mirror is used as', 'opts' => ['Shaving mirror','Driving mirror','Dentist mirror','Makeup mirror'], 'ans' => 1],
            ['q' => 'The core of a transformer is made of', 'opts' => ['Steel','Copper','Soft Iron','Aluminum'], 'ans' => 2],
            ['q' => 'Radioactivity was discovered by', 'opts' => ['Newton','Einstein','Becquerel','Faraday'], 'ans' => 2],
            ['q' => 'p-n junction is a', 'opts' => ['Capacitor','Resistor','Diode','Inductor'], 'ans' => 2],
            ['q' => 'Specific heat capacity unit', 'opts' => ['J/kgK','J/kg','J/K','J'], 'ans' => 0],
            ['q' => 'Upthrust is explained by', 'opts' => ['Newton','Archimedes','Pascal','Hooke'], 'ans' => 1]
        ],
        'Chemistry' => [
            ['q' => 'Atomic number equals', 'opts' => ['protons','neutrons','electrons','mass'], 'ans' => 0],
            ['q' => 'Covalent bond shares', 'opts' => ['protons','neutrons','electrons','photons'], 'ans' => 2],
            ['q' => 'Periodic table arranged by', 'opts' => ['mass','volume','atomic number','density'], 'ans' => 2],
            ['q' => 'Hydrocarbons are', 'opts' => ['organic','inorganic','salts','acids'], 'ans' => 0],
            ['q' => 'pH 1 indicates', 'opts' => ['neutral','basic','acidic','buffer'], 'ans' => 2],
            ['q' => 'Isotopes have same', 'opts' => ['Mass number','Neutron number','Proton number','Physical properties'], 'ans' => 2],
            ['q' => 'Boyle’s law relates', 'opts' => ['P and T','V and T','P and V','P, V and T'], 'ans' => 2],
            ['q' => 'Oxidation is', 'opts' => ['Loss of electrons','Gain of electrons','Gain of Hydrogen','Loss of Oxygen'], 'ans' => 0],
            ['q' => 'Alkanes have the general formula', 'opts' => ['CnH2n+2','CnH2n','CnH2n-2','CnHn'], 'ans' => 0],
            ['q' => 'A catalyst', 'opts' => ['Starts a reaction','Stops a reaction','Alters reaction rate','Is consumed'], 'ans' => 2],
            ['q' => 'Hardness of water is caused by', 'opts' => ['Ca and Mg ions','Na and K ions','Cl and F ions','H and OH ions'], 'ans' => 0],
            ['q' => 'Sulphur (IV) Oxide is used as', 'opts' => ['Bleaching agent','Fertilizer','Fuel','Food'], 'ans' => 0],
            ['q' => 'The most abundant gas in air is', 'opts' => ['Oxygen','Nitrogen','Argon','Carbon Dioxide'], 'ans' => 1],
            ['q' => 'Ethanol is an', 'opts' => ['Alkane','Alkene','Alkanol','Alkanoic Acid'], 'ans' => 2],
            ['q' => 'Faraday’s laws relate to', 'opts' => ['Electrolysis','Gas laws','Thermodynamics','Kinetics'], 'ans' => 0]
        ],
        'Biology' => [
            ['q' => 'Cell is the unit of', 'opts' => ['life','matter','energy','time'], 'ans' => 0],
            ['q' => 'Photosynthesis occurs in', 'opts' => ['mitochondria','chloroplasts','nucleus','ribosome'], 'ans' => 1],
            ['q' => 'Ecology studies', 'opts' => ['cells','organs','interactions','atoms'], 'ans' => 2],
            ['q' => 'Human reproduction involves', 'opts' => ['meiosis','osmosis','diffusion','evaporation'], 'ans' => 0],
            ['q' => 'Protein digestion starts in', 'opts' => ['mouth','stomach','intestine','liver'], 'ans' => 1],
            ['q' => 'The powerhouse of the cell is', 'opts' => ['Nucleus','Mitochondria','Ribosome','Vacuole'], 'ans' => 1],
            ['q' => 'Osmosis involves movement of', 'opts' => ['Solute','Solvent','Ions','Gases'], 'ans' => 1],
            ['q' => 'Xylem transports', 'opts' => ['Food','Water','Oxygen','Hormones'], 'ans' => 1],
            ['q' => 'A group of similar cells is a', 'opts' => ['System','Organ','Tissue','Organism'], 'ans' => 2],
            ['q' => 'Short-sightedness is corrected by', 'opts' => ['Convex lens','Concave lens','Plane mirror','Bifocal lens'], 'ans' => 1],
            ['q' => 'Malaria is caused by', 'opts' => ['Virus','Bacteria','Plasmodium','Fungi'], 'ans' => 2],
            ['q' => 'The mammalian heart has', 'opts' => ['2 chambers','3 chambers','4 chambers','5 chambers'], 'ans' => 2],
            ['q' => 'Photosynthesis product is', 'opts' => ['Protein','Glucose','Fat','Vitamin'], 'ans' => 1],
            ['q' => 'Which is an abiotic factor?', 'opts' => ['Predator','Temperature','Bacteria','Plant'], 'ans' => 1],
            ['q' => 'Mendel is the father of', 'opts' => ['Evolution','Genetics','Cytology','Anatomy'], 'ans' => 1]
        ],
        'Economics' => [
            ['q' => 'Economics is primarily concerned with', 'opts' => ['wealth','choices','politics','history'], 'ans' => 1],
            ['q' => 'Demand law states that price and quantity demanded are', 'opts' => ['directly related','inversely related','unrelated','constant'], 'ans' => 1],
            ['q' => 'Elasticity measures', 'opts' => ['taste','responsiveness','size','speed'], 'ans' => 1],
            ['q' => 'Money functions include', 'opts' => ['investment','unit of account','taxation','production'], 'ans' => 1],
            ['q' => 'GDP stands for', 'opts' => ['Gross Domestic Product','General Domestic Price','Global Demand Price','Gross Demand Product'], 'ans' => 0],
            ['q' => 'Scale of preference helps in', 'opts' => ['Making choices','Production','Distribution','Exchange'], 'ans' => 0],
            ['q' => 'Opportunity cost is', 'opts' => ['Money cost','Alternative forgone','Real cost','Fixed cost'], 'ans' => 1],
            ['q' => 'Factors of production include', 'opts' => ['Land, Labor, Capital, Entrepreneur','Money, Bank, Market, Price','Gold, Silver, Bronze, Iron','Import, Export, Trade, Aid'], 'ans' => 0],
            ['q' => 'A monopolist is a', 'opts' => ['Sole seller','Sole buyer','Government','Partnership'], 'ans' => 0],
            ['q' => 'Inflation is', 'opts' => ['Fall in prices','Rise in prices','Constant prices','Zero prices'], 'ans' => 1],
            ['q' => 'Central Bank is responsible for', 'opts' => ['Monetary policy','Fiscal policy','Trade policy','Education policy'], 'ans' => 0],
            ['q' => 'Utility means', 'opts' => ['Usefulness','Satisfaction','Price','Value'], 'ans' => 1],
            ['q' => 'Public Limited Companies issue', 'opts' => ['Shares','Bonds','Notes','Bills'], 'ans' => 0],
            ['q' => 'Budget deficit means', 'opts' => ['Revenue > Expenditure','Expenditure > Revenue','Revenue = Expenditure','No budget'], 'ans' => 1],
            ['q' => 'ECOWAS promotes', 'opts' => ['Regional trade','Global war','Sports','Religion'], 'ans' => 0]
        ],
        'Government' => [
            ['q' => 'Democracy is a government by', 'opts' => ['few','one','people','army'], 'ans' => 2],
            ['q' => 'Separation of powers relates to', 'opts' => ['sectors','arms of government','regions','parties'], 'ans' => 1],
            ['q' => 'Constitution is a', 'opts' => ['policy','basic law','speech','campaign'], 'ans' => 1],
            ['q' => 'ECOWAS is a', 'opts' => ['court','regional body','company','party'], 'ans' => 1],
            ['q' => 'Rule of law implies', 'opts' => ['arbitrariness','supremacy of law','militarism','elitism'], 'ans' => 1],
            ['q' => 'A system with two levels of government is', 'opts' => ['Unitary','Federal','Confederal','Monarchy'], 'ans' => 1],
            ['q' => 'The executive arm', 'opts' => ['Makes laws','Interprets laws','Implements laws','Punishes laws'], 'ans' => 2],
            ['q' => 'Franchise is the right to', 'opts' => ['Speak','Vote','Travel','Work'], 'ans' => 1],
            ['q' => 'Sovereignty resides in the', 'opts' => ['State','President','Army','Police'], 'ans' => 0],
            ['q' => 'Indirect Rule was introduced by', 'opts' => ['Lugard','Clifford','Macaulay','Azikiwe'], 'ans' => 0],
            ['q' => 'The first military coup in Nigeria was in', 'opts' => ['1960','1963','1966','1979'], 'ans' => 2],
            ['q' => 'UNO headquarters is in', 'opts' => ['London','Paris','New York','Geneva'], 'ans' => 2],
            ['q' => 'OAU is now', 'opts' => ['AU','ECOWAS','UN','EU'], 'ans' => 0],
            ['q' => 'Bicameral legislature has', 'opts' => ['One chamber','Two chambers','Three chambers','Four chambers'], 'ans' => 1],
            ['q' => 'Pressure groups seek to', 'opts' => ['Influence policy','Take power','Make profit','Build roads'], 'ans' => 0]
        ],
        'Literature' => [
            ['q' => 'Prose is a form of', 'opts' => ['drama','poetry','narrative','music'], 'ans' => 2],
            ['q' => 'A sonnet has', 'opts' => ['10 lines','12 lines','14 lines','16 lines'], 'ans' => 2],
            ['q' => 'Dramatic irony occurs when', 'opts' => ['audience knows more','actor knows more','writer knows more','none'], 'ans' => 0],
            ['q' => 'Metaphor is a', 'opts' => ['comparison using like','direct comparison','exaggeration','sound device'], 'ans' => 1],
            ['q' => 'African literature includes', 'opts' => ['only poetry','oral and written forms','only drama','only prose'], 'ans' => 1],
            ['q' => '"The Invisible Teacher" is a', 'opts' => ['Novel','Play','Poem','Biography'], 'ans' => 0],
            ['q' => 'A stanza of four lines is a', 'opts' => ['Couplet','Quatrain','Sestet','Octave'], 'ans' => 1],
            ['q' => 'Theme refers to', 'opts' => ['The setting','The central idea','The character','The plot'], 'ans' => 1],
            ['q' => 'Protagonist is the', 'opts' => ['Villain','Hero/Heroine','Clown','Narrator'], 'ans' => 1],
            ['q' => 'Chinua Achebe wrote', 'opts' => ['The Lion and the Jewel','Things Fall Apart','The Concubine','Trials of Brother Jero'], 'ans' => 1],
            ['q' => 'Wole Soyinka is a', 'opts' => ['Novelist','Poet','Playwright','All of the above'], 'ans' => 3],
            ['q' => '"Enjambment" is found in', 'opts' => ['Drama','Poetry','Prose','News'], 'ans' => 1],
            ['q' => 'Personification gives human qualities to', 'opts' => ['Humans','Animals','Inanimate objects','Gods'], 'ans' => 2],
            ['q' => 'Hyperbole is', 'opts' => ['Understatement','Exaggeration','Irony','Satire'], 'ans' => 1],
            ['q' => 'Climax is the point of', 'opts' => ['Highest tension','Beginning','Resolution','Introduction'], 'ans' => 0]
        ],
        'CRS' => [
            ['q' => 'Who was the first king of Israel?', 'opts' => ['David','Saul','Solomon','Samuel'], 'ans' => 1],
            ['q' => 'The detailed account of creation is found in Genesis chapter', 'opts' => ['1 and 2','3 and 4','5 and 6','7 and 8'], 'ans' => 0],
            ['q' => 'The son of Abraham by Hagar was', 'opts' => ['Isaac','Ishmael','Esau','Jacob'], 'ans' => 1],
            ['q' => 'Jesus was baptized by', 'opts' => ['Peter','Paul','John the Baptist','James'], 'ans' => 2],
            ['q' => 'The first martyr of the Christian Church was', 'opts' => ['Peter','Paul','Stephen','James'], 'ans' => 2],
            ['q' => 'God called Abraham from', 'opts' => ['Ur','Haran','Canaan','Egypt'], 'ans' => 0],
            ['q' => 'Joseph was sold for', 'opts' => ['20 shekels','30 shekels','40 shekels','50 shekels'], 'ans' => 0],
            ['q' => 'The Ten Commandments were given at', 'opts' => ['Sinai','Horeb','Carmel','Olive'], 'ans' => 0],
            ['q' => 'Who denied Jesus three times?', 'opts' => ['Judas','Peter','Thomas','Andrew'], 'ans' => 1],
            ['q' => 'The Holy Spirit descended on Pentecost in form of', 'opts' => ['Dove','Fire','Wind','Water'], 'ans' => 1],
            ['q' => 'Saul was converted on the way to', 'opts' => ['Jerusalem','Damascus','Antioch','Rome'], 'ans' => 1],
            ['q' => '"I am the way, the truth and the life" was said by', 'opts' => ['Moses','Elijah','Jesus','Paul'], 'ans' => 2],
            ['q' => 'The shortest verse in the Bible is', 'opts' => ['Jesus wept','Pray without ceasing','Rejoice always','God is love'], 'ans' => 0],
            ['q' => 'Who wrote the Acts of the Apostles?', 'opts' => ['Matthew','Mark','Luke','John'], 'ans' => 2],
            ['q' => 'The fruit of the Spirit includes', 'opts' => ['Love','Hate','Envy','Pride'], 'ans' => 0]
        ],
    ];
}
if ($mode === 'practice') {
    $sub = $_GET['practice'] ?? 'English';
    
    // Check if we are viewing results
    if (isset($_GET['done']) && isset($_SESSION['practice_result'])) {
        $result = $_SESSION['practice_result'];
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validate_csrf()) {
            setFlash('error', 'Security token mismatch.');
            redirect(SITE_URL . '/jamb-cbt.php?practice=' . urlencode($sub));
        }
        // Process submission
        $qs = $_SESSION['practice_qs'] ?? [];
        if (empty($qs)) {
            // Session expired or direct access without questions
            redirect(SITE_URL . '/jamb-cbt.php?practice=' . urlencode($sub));
        }
        
        $score = 0;
        $total = count($qs);
        $userAnswers = [];
        
        foreach ($qs as $i => $q) {
            $ans = isset($_POST['q_'.$i]) ? (int)$_POST['q_'.$i] : -1;
            $userAnswers[$i] = $ans;
            if ($ans === $q['ans']) {
                $score++;
            }
        }
        
        $_SESSION['practice_result'] = [
            'score' => $score,
            'total' => $total,
            'questions' => $qs,
            'userAnswers' => $userAnswers
        ];
        
        redirect(SITE_URL . '/jamb-cbt.php?practice=' . urlencode($sub) . '&done=1');
        
    } else {
        // Start new practice - generate questions
        $qs = generate_ai_subject_questions($sub, 10);
        $_SESSION['practice_qs'] = $qs;
        // Unset previous result so we don't show old results on new practice start
        if (isset($_SESSION['practice_result'])) unset($_SESSION['practice_result']);
    }
}
if ($mode === 'exam') {
    $chosen = $_SESSION['cbt_subjects'] ?? ['English','Mathematics','Physics','Chemistry'];
    $examQs = [];
    $targetPerSubject = [];
    $cfg = get_cbt_settings();
    foreach ($chosen as $s) {
        $targetPerSubject[$s] = ($s === 'English') ? (int)$cfg['english_count'] : (int)$cfg['other_count'];
    }
    foreach ($chosen as $s) {
        $needed = $targetPerSubject[$s];
        $gen = generate_ai_subject_questions($s, $needed);
        foreach ($gen as $q) {
            $examQs[] = ['subject' => $s] + $q;
        }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validate_csrf()) {
             setFlash('error', 'Security token mismatch.');
             redirect(SITE_URL . '/jamb-cbt.php?exam=1');
        }
        $score = 0;
        $perSub = [];
        foreach ($examQs as $i => $q) {
            $ans = isset($_POST['q_'.$i]) ? (int)$_POST['q_'.$i] : -1;
            $correct = $q['ans'];
            if (!isset($perSub[$q['subject']])) $perSub[$q['subject']] = ['total'=>0,'correct'=>0];
            $perSub[$q['subject']]['total']++;
            if ($ans === $correct) {
                $score++;
                $perSub[$q['subject']]['correct']++;
            }
        }
        $_SESSION['cbt_result'] = ['score'=>$score,'total'=>count($examQs),'breakdown'=>$perSub];
        redirect(SITE_URL . '/jamb-cbt.php?exam=1&done=1');
    }
    $result = isset($_GET['done']) ? ($_SESSION['cbt_result'] ?? null) : null;
    if (!isset($_SESSION['cbt_started_at'])) {
        $_SESSION['cbt_started_at'] = time();
    }
    $durationMinutes = 120;
    $endTs = $_SESSION['cbt_started_at'] + ($durationMinutes * 60);
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JAMB CBT | <?php echo SITE_NAME; ?></title>
    
    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0B2C4D',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="manifest" href="<?php echo SITE_URL; ?>/manifest.json">
    <meta name="theme-color" content="#0B2C4D">
    
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100 transition-colors duration-300 flex flex-col min-h-screen" x-data="{ 
    darkMode: localStorage.getItem('darkMode') === 'true',
    mobileMenuOpen: false,
    toggleTheme() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('darkMode', this.darkMode);
        if (this.darkMode) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
}" x-init="$watch('darkMode', val => val ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark')); if(darkMode) document.documentElement.classList.add('dark');">
    
    <nav class="bg-primary-900 text-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center relative">
            <a href="<?php echo SITE_URL; ?>/dashboard/" class="text-2xl font-bold tracking-tight flex items-center gap-2">
                <span class="bg-white text-primary-900 px-2 py-1 rounded-md text-lg">O</span> Oleku
            </a>
            
            <div class="flex items-center gap-6">
                <div class="hidden md:flex items-center gap-6 text-sm font-medium">
                    <a href="<?php echo SITE_URL; ?>/dashboard/" class="hover:text-primary-200 transition">Home</a>
                    <a href="<?php echo SITE_URL; ?>/jamb-subjects.php" class="text-white font-bold border-b-2 border-white pb-0.5">JAMB Prep</a>
                    <a href="<?php echo SITE_URL; ?>/university/index.php" class="hover:text-primary-200 transition">University</a>
                    <?php if (isAdmin()): ?>
                        <a href="<?php echo SITE_URL; ?>/admin/" class="hover:text-primary-200 transition">Admin</a>
                    <?php endif; ?>
                    <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="bg-white/10 px-4 py-2 rounded-lg hover:bg-white/20 transition">Logout</a>
                </div>

                <!-- Dark Mode Toggle -->
                <button @click="toggleTheme()" class="p-2 rounded-full hover:bg-white/10 transition focus:outline-none" aria-label="Toggle Dark Mode">
                    <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </button>
                
                <!-- Mobile Menu Button -->
                <button class="md:hidden p-2 text-white focus:outline-none" @click="mobileMenuOpen = !mobileMenuOpen">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden absolute top-full left-0 right-0 bg-primary-900 border-t border-white/10 shadow-xl z-50" 
             @click.away="mobileMenuOpen = false"
             x-cloak>
            <div class="flex flex-col p-4 space-y-4 text-center">
                <a href="<?php echo SITE_URL; ?>/dashboard/" class="text-white hover:text-primary-200 transition py-2">Home</a>
                <a href="<?php echo SITE_URL; ?>/jamb-subjects.php" class="text-white font-bold bg-white/10 rounded py-2">JAMB Prep</a>
                <a href="<?php echo SITE_URL; ?>/university/index.php" class="text-white hover:text-primary-200 transition py-2">University</a>
                <?php if (isAdmin()): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/" class="text-white hover:text-primary-200 transition py-2">Admin</a>
                <?php endif; ?>
                <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="bg-white/10 text-white px-4 py-3 rounded-lg hover:bg-white/20 transition font-medium">Logout</a>
            </div>
        </div>
    </nav>

    <main class="flex-grow py-8">
    <?php if ($mode === 'setup'): ?>
    <section class="container mx-auto px-4 max-w-3xl">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 md:p-8">
            <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Set Up CBT Exam</h2>
            <?php displayFlash(); ?>
            <form method="post" action="<?php echo SITE_URL; ?>/jamb-cbt.php?setup=1">
                <?php echo csrf_field(); ?>
                <p class="mb-4 text-gray-600 dark:text-gray-300">Select English and 3 other subjects for your practice exam.</p>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
                    <?php foreach ($subjects as $s): ?>
                    <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition">
                        <input type="checkbox" name="subjects[]" value="<?php echo $s; ?>" <?php echo $s==='English'?'checked disabled':''; ?> class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-gray-700 dark:text-gray-200"><?php echo $s; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <button class="bg-primary-600 hover:bg-primary-700 text-white px-8 py-3 rounded-lg font-medium transition shadow-sm hover:shadow-md w-full md:w-auto">Start Exam</button>
            </form>
        </div>
    </section>
    <?php elseif ($mode === 'practice'): ?>
    <section class="container mx-auto px-4 max-w-3xl">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 md:p-8">
            <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white"><?php echo $sub; ?> Practice</h2>
            
            <?php if (isset($result)): ?>
                <div class="mb-8 p-6 bg-blue-50 dark:bg-blue-900/20 rounded-xl text-center border border-blue-100 dark:border-blue-800/30">
                    <h3 class="text-xl font-bold text-blue-800 dark:text-blue-300">Your Score</h3>
                    <p class="text-5xl font-bold text-blue-600 dark:text-blue-400 my-3"><?php echo $result['score']; ?> <span class="text-2xl text-blue-400 dark:text-blue-500">/ <?php echo $result['total']; ?></span></p>
                    <p class="text-gray-600 dark:text-gray-400 font-medium"><?php echo round(($result['score']/$result['total'])*100); ?>%</p>
                </div>
                
                <div class="space-y-8">
                    <?php foreach ($result['questions'] as $i => $q): ?>
                    <div class="border-b border-gray-100 dark:border-gray-700 pb-6 last:border-0">
                        <p class="font-semibold mb-3 text-lg text-gray-900 dark:text-white"><?php echo ($i+1).'. '.$q['q']; ?></p>
                        <div class="ml-4 space-y-2">
                            <?php foreach ($q['opts'] as $oi => $opt): ?>
                                <?php 
                                    $isCorrect = ($oi === $q['ans']);
                                    $isSelected = ($result['userAnswers'][$i] === $oi);
                                    $class = 'text-gray-600 dark:text-gray-400';
                                    $icon = '';
                                    if ($isCorrect) {
                                        $class = 'text-green-600 dark:text-green-400 font-bold';
                                        $icon = '✓';
                                    } elseif ($isSelected) {
                                        $class = 'text-red-600 dark:text-red-400 line-through';
                                        $icon = '✗';
                                    }
                                ?>
                                <div class="<?php echo $class; ?> flex items-center gap-2">
                                    <span><?php echo $opt; ?></span>
                                    <?php if ($icon): ?><span><?php echo $icon; ?></span><?php endif; ?>
                                    <?php if ($isSelected && !$isCorrect): ?>
                                        <span class="text-green-600 dark:text-green-400 text-sm ml-2">(Correct: <?php echo $q['opts'][$q['ans']]; ?>)</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-8 flex gap-4 justify-center">
                    <a href="<?php echo SITE_URL; ?>/jamb-cbt.php?practice=<?php echo urlencode($sub); ?>" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2.5 rounded-lg font-medium transition shadow-sm">Practice Again</a>
                    <a href="<?php echo SITE_URL; ?>/dashboard/" class="border border-primary-600 text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 px-6 py-2.5 rounded-lg font-medium transition">Dashboard</a>
                </div>
            <?php else: ?>
                <form method="post" action="<?php echo SITE_URL; ?>/jamb-cbt.php?practice=<?php echo urlencode($sub); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="space-y-8">
                        <?php foreach ($qs as $i => $q): ?>
                        <div>
                            <p class="font-semibold mb-3 text-lg text-gray-900 dark:text-white"><?php echo ($i+1).'. '.$q['q']; ?></p>
                            <div class="space-y-2">
                                <?php foreach ($q['opts'] as $oi => $opt): ?>
                                <label class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition border border-transparent hover:border-gray-200 dark:hover:border-gray-600">
                                    <input type="radio" name="q_<?php echo $i; ?>" value="<?php echo $oi; ?>" class="w-4 h-4 text-primary-600 focus:ring-primary-500 border-gray-300">
                                    <span class="text-gray-700 dark:text-gray-300"><?php echo $opt; ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-8">
                        <button class="bg-primary-600 hover:bg-primary-700 text-white px-8 py-3 rounded-lg font-medium transition shadow-sm hover:shadow-md w-full md:w-auto">Submit Practice</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </section>
    <?php elseif ($mode === 'exam'): ?>
    <section class="container mx-auto px-4 max-w-4xl">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 md:p-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">CBT Exam</h2>
                <?php if (!$result): ?>
                <div class="p-3 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 rounded-lg border border-red-100 dark:border-red-800/30 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="font-semibold">Time Left:</span>
                    <span id="timer" class="font-mono text-lg font-bold"></span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($result): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-6 rounded-xl border border-blue-100 dark:border-blue-800/30 text-center">
                        <p class="text-sm text-blue-600 dark:text-blue-400 font-medium uppercase tracking-wide">Total Score</p>
                        <p class="text-5xl font-bold text-blue-700 dark:text-blue-300 my-2"><?php echo $result['score']; ?> <span class="text-2xl text-blue-400 dark:text-blue-500">/ <?php echo $result['total']; ?></span></p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <?php foreach ($result['breakdown'] as $s => $stats): ?>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-100 dark:border-gray-600">
                            <p class="font-semibold text-gray-900 dark:text-white mb-1"><?php echo $s; ?></p>
                            <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo $stats['correct']; ?> / <?php echo $stats['total']; ?> Correct</p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="flex gap-4 justify-center">
                    <a href="<?php echo SITE_URL; ?>/jamb-cbt.php?setup=1" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2.5 rounded-lg font-medium transition shadow-sm">Retake Exam</a>
                    <a href="<?php echo SITE_URL; ?>/dashboard/" class="border border-primary-600 text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 px-6 py-2.5 rounded-lg font-medium transition">Dashboard</a>
                </div>
            <?php else: ?>
                <form method="post" id="examForm" action="<?php echo SITE_URL; ?>/jamb-cbt.php?exam=1">
                    <?php echo csrf_field(); ?>
                    <div class="space-y-8">
                        <?php foreach ($examQs as $i => $q): ?>
                        <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-700">
                            <p class="text-xs font-semibold text-primary-600 dark:text-primary-400 mb-2 uppercase tracking-wide"><?php echo $q['subject']; ?></p>
                            <p class="font-medium mb-3 text-gray-900 dark:text-white text-lg"><?php echo ($i+1).'. '.$q['q']; ?></p>
                            <div class="space-y-2">
                                <?php foreach ($q['opts'] as $oi => $opt): ?>
                                <label class="flex items-center gap-3 p-2 rounded hover:bg-white dark:hover:bg-gray-600 cursor-pointer transition">
                                    <input type="radio" name="q_<?php echo $i; ?>" value="<?php echo $oi; ?>" class="w-4 h-4 text-primary-600 focus:ring-primary-500 border-gray-300">
                                    <span class="text-gray-700 dark:text-gray-300"><?php echo $opt; ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-700">
                        <button class="bg-primary-600 hover:bg-primary-700 text-white px-8 py-3 rounded-lg font-medium transition shadow-sm hover:shadow-md w-full md:w-auto" id="submitBtn">Submit Exam</button>
                    </div>
                </form>
                <script>
                    (function(){
                        var endTs = <?php echo $endTs; ?> * 1000;
                        function fmt(sec){
                            var m = Math.floor(sec/60), s = sec%60;
                            return (m<10?'0':'')+m+':' + (s<10?'0':'')+s;
                        }
                        function tick(){
                            var now = Date.now();
                            var rem = Math.max(0, Math.floor((endTs - now)/1000));
                            var el = document.getElementById('timer');
                            if (el) el.textContent = fmt(rem);
                            if (rem <= 0) {
                                var radios = document.querySelectorAll('input[type=radio]');
                                radios.forEach(function(b){ b.disabled = true; });
                                var form = document.getElementById('examForm');
                                if (form) form.submit();
                            } else {
                                setTimeout(tick, 1000);
                            }
                        }
                        tick();
                    })();
                </script>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>
    </main>

    <footer class="bg-primary-900 text-white/60 py-8 border-t border-white/10 mt-auto">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> Oleku. Built for Excellence.</p>
        </div>
    </footer>
</body>
</html>
