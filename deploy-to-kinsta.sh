#!/bin/bash

echo "📦 Deploying to Kinsta staging server..."

ssh -p 30285 projectcamino@34.174.186.154 << 'EOF'
  cd /www/projectcamino_837/public
  echo "🔁 Pulling latest code from GitHub..."
  git pull origin main
  echo "✅ Deployment complete!"
EOF
