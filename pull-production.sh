#!/bin/bash
# Pull latest code to production server
# Run this via SSH on your server

cd /home/s3vtgroup/public_html

echo "=== Pulling Latest Code to Production ==="
echo "Directory: /home/s3vtgroup/public_html"
echo ""

# Check current status
echo "=== Current Git Status ==="
git status

echo ""
echo "=== Stashing any local changes ==="
git stash save "Production changes before pull - $(date)"

echo ""
echo "=== Fetching latest changes from GitHub ==="
git fetch origin

echo ""
echo "=== Pulling latest code from origin/main ==="
echo "Using --no-ff to handle divergence..."
git pull origin main --no-ff

if [ $? -eq 0 ]; then
    echo ""
    echo "=== Success! Code updated. ==="
    echo ""
    echo "If you had stashed changes and need them back:"
    echo "  git stash list    # See stashed changes"
    echo "  git stash pop     # Restore stashed changes"
else
    echo ""
    echo "=== Divergence detected - attempting merge ==="
    echo "Merging remote changes..."
    git pull origin main --no-ff -m "Merge remote changes from GitHub"
    
    if [ $? -eq 0 ]; then
        echo ""
        echo "=== Merge successful! ==="
        echo "Pushing merged result..."
        git push origin main
        echo ""
        echo "=== Complete! Production is now in sync. ==="
    else
        echo ""
        echo "=== Merge conflicts detected! ==="
        echo "Please resolve conflicts manually:"
        echo "1. Check conflicted files: git status"
        echo "2. Edit files to resolve conflicts"
        echo "3. Stage resolved files: git add ."
        echo "4. Complete merge: git commit"
        echo "5. Push: git push origin main"
        exit 1
    fi
fi
