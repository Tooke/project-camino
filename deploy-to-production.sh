#!/bin/bash

echo "🚀 Deploying to Kinsta PRODUCTION server..."

ssh -p 52382 projectcamino@34.174.186.154 << 'EOF'
  cd /www/projectcamino_837/public
  echo "🔁 Pulling latest code from GitHub..."
  git pull origin main
  echo "✅ Production deployment complete!"
EOF
