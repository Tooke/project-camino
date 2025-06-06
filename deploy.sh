#!/bin/bash

# Step 1: Navigate to the script's directory (your project root)
cd "$(dirname "$0")"

# Step 2: Confirm Git repository
if [ ! -d ".git" ]; then
  echo "❌ This is not a Git repository."
  exit 1
fi

# Step 3: Add all changes
git add .

# Step 4: Prompt for commit message
echo -n "Enter commit message: "
read commit_message

# Step 5: Commit changes
git commit -m "$commit_message"

# Step 6: Push to GitHub
git push

# Step 7: Trigger Kinsta Deployment (optional)
if [ -f "./deploy-to-kinsta.sh" ]; then
  echo "🚀 Running deploy_to_kinsta.sh..."
  ./deploy-to-kinsta.sh
else
  echo "⚠️ deploy-to-kinsta.sh not found. Skipping Kinsta deployment."
fi