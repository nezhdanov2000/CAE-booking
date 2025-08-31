<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';
$conversationHistory = $input['conversation_history'] ?? [];

if (empty($userMessage)) {
    echo json_encode(['success' => false, 'error' => 'Empty message']);
    exit;
}

// Function to get AI response
function getAIResponse($message, $history = []) {
    // You can integrate with various AI APIs here
    // For now, we'll use a simple rule-based system with some AI-like responses
    
    $message = strtolower(trim($message));
    
    // Simple keyword-based responses
    $responses = [
        'привет' => 'Привет! Как дела? Чем могу помочь?',
        'здравствуй' => 'Здравствуйте! Рад вас видеть. Как я могу вам помочь?',
        'помощь' => 'Конечно! Я могу помочь вам с:\n• Информацией о курсах\n• Расписанием занятий\n• Техническими вопросами\n• Общими вопросами по системе\nЧто именно вас интересует?',
        'курс' => 'У нас есть различные курсы. Вы можете:\n• Просмотреть доступные курсы\n• Записаться на курс\n• Посмотреть расписание\nКакой курс вас интересует?',
        'расписание' => 'Расписание можно посмотреть в разделе "Schedule" в главном меню. Там вы найдете все доступные временные слоты для записи.',
        'записаться' => 'Чтобы записаться на занятие:\n1. Перейдите в раздел "Available Bookings"\n2. Выберите удобное время\n3. Нажмите "Book"\nНужна помощь с чем-то конкретным?',
        'время' => 'Временные слоты доступны в разделе расписания. Там вы можете увидеть все свободные часы для записи.',
        'студент' => 'Как студент, вы можете:\n• Просматривать доступные слоты\n• Записываться на занятия\n• Просматривать свои записи\n• Отменять записи при необходимости',
        'преподаватель' => 'Как преподаватель, вы можете:\n• Создавать временные слоты\n• Просматривать свои слоты\n• Видеть кто записался\n• Управлять расписанием',
        'спасибо' => 'Пожалуйста! Рад был помочь. Если у вас есть еще вопросы, обращайтесь!',
        'пока' => 'До свидания! Хорошего дня!',
        'до свидания' => 'До свидания! Буду рад помочь снова!'
    ];
    
    // Check for exact matches first
    foreach ($responses as $keyword => $response) {
        if ($message === $keyword) {
            return $response;
        }
    }
    
    // Check for partial matches
    foreach ($responses as $keyword => $response) {
        if (strpos($message, $keyword) !== false) {
            return $response;
        }
    }
    
    // Check for question patterns
    if (strpos($message, 'как') !== false) {
        if (strpos($message, 'записаться') !== false) {
            return 'Чтобы записаться на занятие:\n1. Перейдите в раздел "Available Bookings"\n2. Выберите удобное время\n3. Нажмите "Book"\nЭто очень просто!';
        }
        if (strpos($message, 'создать') !== false && strpos($message, 'слот') !== false) {
            return 'Чтобы создать временной слот:\n1. Перейдите в раздел "Create Slot"\n2. Выберите дату и время\n3. Укажите описание\n4. Нажмите "Create"\nГотово!';
        }
        return 'Я могу помочь вам с различными вопросами. Попробуйте спросить о курсах, расписании, записи на занятия или других функциях системы.';
    }
    
    // Check for greeting patterns
    if (preg_match('/(привет|здравствуй|добрый|hi|hello)/', $message)) {
        return 'Привет! Рад вас видеть. Как я могу вам помочь сегодня?';
    }
    
    // Check for help patterns
    if (preg_match('/(помощь|help|что.*умеешь|что.*можешь)/', $message)) {
        return 'Я ваш AI-ассистент и могу помочь с:\n• Информацией о системе CAE\n• Навигацией по сайту\n• Вопросами о курсах и расписании\n• Технической поддержкой\n• Общими вопросами\nЧто именно вас интересует?';
    }
    
    // Check for "что я могу делать" pattern
    if (preg_match('/(что.*могу|мои.*возможности|что.*умею)/', $message)) {
        return 'В зависимости от вашей роли в системе:\n\nКак студент:\n• Просматривать доступные слоты\n• Записываться на занятия\n• Просматривать свои записи\n• Отменять записи\n\nКак преподаватель:\n• Создавать временные слоты\n• Просматривать свои слоты\n• Видеть кто записался\n• Управлять расписанием';
    }
    
    // Default response
    $defaultResponses = [
        'Интересный вопрос! Давайте разберемся вместе. Можете уточнить, что именно вас интересует?',
        'Хороший вопрос. Я постараюсь помочь. Что конкретно вы хотели бы узнать?',
        'Понимаю ваш интерес. Давайте найдем ответ на ваш вопрос. Можете переформулировать?',
        'Спасибо за вопрос! Я готов помочь. Что именно вас интересует в нашей системе?',
        'Отличный вопрос! Давайте разберемся. Можете уточнить детали?'
    ];
    
    return $defaultResponses[array_rand($defaultResponses)];
}

// Function to integrate with external AI API (example with OpenAI)
function getOpenAIResponse($message, $history = []) {
    // You can uncomment and configure this for real AI integration
    /*
    $apiKey = 'your-openai-api-key';
    $url = 'https://api.openai.com/v1/chat/completions';
    
    $messages = [
        [
            'role' => 'system',
            'content' => 'Ты полезный AI-ассистент для образовательной платформы CAE. Отвечай на русском языке. Помогай пользователям с вопросами о курсах, расписании, записи на занятия и других функциях системы.'
        ]
    ];
    
    // Add conversation history
    foreach ($history as $msg) {
        $messages[] = [
            'role' => $msg['role'] === 'user' ? 'user' : 'assistant',
            'content' => $msg['content']
        ];
    }
    
    // Add current message
    $messages[] = [
        'role' => 'user',
        'content' => $message
    ];
    
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => $messages,
        'max_tokens' => 500,
        'temperature' => 0.7
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if (isset($result['choices'][0]['message']['content'])) {
        return $result['choices'][0]['message']['content'];
    }
    */
    
    // For now, use the simple rule-based system
    return getAIResponse($message, $history);
}

// Get AI response
$aiResponse = getOpenAIResponse($userMessage, $conversationHistory);

// Log the conversation (optional)
$logData = [
    'user_id' => $_SESSION['user_id'],
    'user_message' => $userMessage,
    'ai_response' => $aiResponse,
    'timestamp' => date('Y-m-d H:i:s')
];

// You can save this to database if needed
// saveToDatabase($logData);

// Return response
echo json_encode([
    'success' => true,
    'response' => $aiResponse,
    'timestamp' => date('Y-m-d H:i:s')
]);

// Function to save conversation to database (optional)
function saveToDatabase($logData) {
    // Connect to database
    require_once 'db.php';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("INSERT INTO chat_logs (user_id, user_message, ai_response, timestamp) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $logData['user_id'],
            $logData['user_message'],
            $logData['ai_response'],
            $logData['timestamp']
        ]);
    } catch (PDOException $e) {
        // Log error silently
        error_log("Chat log error: " . $e->getMessage());
    }
}
?>
