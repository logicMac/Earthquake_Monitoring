# QuakeBot Setup Guide

QuakeBot is an AI-powered assistant that helps you understand earthquake data, answer questions, and provide insights from your monitoring system.

## Features

- 🤖 Natural language queries about earthquake data
- 📊 Real-time data analysis and statistics
- 📚 Educational assistant for earthquake terminology
- 💬 Friendly, conversational interface
- 🌓 Dark/Light mode support
- 📱 Fully mobile responsive

## Setup Instructions

### 1. Get Your Free Groq API Key

1. Visit [https://console.groq.com/](https://console.groq.com/)
2. Sign up for a free account (no credit card required)
3. Navigate to API Keys section
4. Click "Create API Key"
5. Copy your API key (starts with `gsk_...`)

### 2. Configure the System

Open `config/database.php` and replace `YOUR_GROQ_API_KEY` with your actual API key:

```php
// Groq API Configuration - QuakeBot
define('GROQ_API_KEY', 'gsk_your_actual_api_key_here');
```

### 3. Access QuakeBot

1. Log in to your dashboard
2. Click "QuakeBot" in the navigation menu
3. Start asking questions!

## Example Questions

### Data Queries
- "What was the latest earthquake detected?"
- "How many high intensity events this month?"
- "Show me statistics for the past week"
- "When was the last SMS alert sent?"

### Educational
- "Explain the MMI scale"
- "What does Gal mean?"
- "What's the difference between Level-1 and Level-3 alerts?"
- "How does the MPU6050 sensor work?"

### Insights
- "Are there any patterns in recent activity?"
- "What should I do during a Level-3 alert?"
- "Summarize today's seismic activity"

## Technical Details

### Model Information
- **Provider**: Groq (Free Tier)
- **Model**: Llama 3.3 70B Versatile
- **Speed**: Ultra-fast inference (~500 tokens/sec)
- **Cost**: Free with generous limits

### API Limits (Free Tier)
- 30 requests per minute
- 14,400 requests per day
- More than enough for typical usage

### Data Context
QuakeBot has access to:
- Total events recorded
- Latest event details
- Recent 10 events
- Statistics (max, average, SMS sent)
- High intensity event counts
- Emergency event counts

### Security
- Requires user authentication
- API key stored securely in config
- No data sent to external services except Groq API
- All queries are logged for your security

## Troubleshooting

### "QuakeBot is not configured yet"
- Make sure you've added your Groq API key in `config/database.php`
- Check that the key starts with `gsk_`

### "API error (HTTP 401)"
- Your API key is invalid or expired
- Generate a new key from Groq console

### "Connection error"
- Check your internet connection
- Verify InfinityFree allows outbound HTTPS connections
- Try again in a few moments

### Slow Responses
- Groq is usually very fast (<2 seconds)
- If slow, check your hosting provider's connection
- Free hosting may have bandwidth limitations

## InfinityFree Compatibility

✅ **Compatible**: QuakeBot works on InfinityFree hosting
- File named `quakebot.php` (not "chat" or "ai" - those trigger 403 errors)
- API handler at `api/quakebot_handler.php`
- Uses standard PHP cURL for API calls

## Privacy & Data

- Your earthquake data stays on your server
- Only user questions and system stats are sent to Groq
- No personal information is transmitted
- Groq does not store conversation history
- All data is encrypted in transit (HTTPS)

## Customization

### Adjust Response Length
Edit `api/quakebot_handler.php`:
```php
'max_tokens' => 500,  // Increase for longer responses
```

### Change Temperature (Creativity)
```php
'temperature' => 0.7,  // 0.0 = factual, 1.0 = creative
```

### Modify System Prompt
Edit the `buildSystemPrompt()` function to customize QuakeBot's personality and knowledge.

## Support

For issues or questions:
1. Check this guide first
2. Review error messages in browser console
3. Check PHP error logs on your server
4. Verify API key is correct

## Credits

- **AI Model**: Llama 3.3 70B by Meta
- **API Provider**: Groq (https://groq.com)
- **Integration**: ND-SCPM Earthquake Monitoring System
- **School**: Notre Dame - Siena College of Polomolok

---

**Note**: QuakeBot is designed for educational and monitoring purposes. For emergency situations, always follow official protocols and contact proper authorities.
