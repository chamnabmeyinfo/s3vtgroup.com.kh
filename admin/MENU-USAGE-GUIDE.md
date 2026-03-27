# Menu System Usage Guide

## 📋 Table of Contents
1. [Accessing the Menu System](#accessing-the-menu-system)
2. [Creating a New Menu](#creating-a-new-menu)
3. [Adding Menu Items](#adding-menu-items)
4. [Organizing Menu Items](#organizing-menu-items)
5. [Using Mega Menu Features](#using-mega-menu-features)
6. [Selecting Categories for Products Menu](#selecting-categories-for-products-menu)
7. [Assigning Menus to Locations](#assigning-menus-to-locations)

---

## 🚀 Accessing the Menu System

1. **Login to Admin Panel**
   - Go to: `http://localhost:8080/admin/login.php`
   - Enter your admin credentials

2. **Navigate to Menus**
   - Click on **"Menus"** in the left sidebar
   - Or go directly to: `http://localhost:8080/admin/menus.php`

---

## ➕ Creating a New Menu

1. **Click "Add New Menu"** button (top right)
2. **Fill in Menu Details:**
   - **Menu Name**: Enter a name (e.g., "Main Navigation")
   - **Menu Slug**: Auto-generated from name (or customize)
   - **Description**: Optional description
3. **Click "Save Menu"**
4. The page will refresh and show the menu editor

---

## 📝 Adding Menu Items

### Step 1: Open the Menu Editor
- Click **"Edit"** on any menu from the menus list
- Or click **"Add New Menu"** and save it first

### Step 2: Click "Add Item" Button
- Click the **"Add Item"** button (usually at the top)

### Step 3: Choose Item Type

You can add different types of menu items:

#### **Custom Link**
- **Use for**: Any custom URL or page
- **Fields**: Title, URL
- **Example**: "About Us" → `/about-us.php`

#### **Category**
- **Use for**: Product categories
- **Fields**: Select a category from dropdown
- **Auto-generates**: Title and URL from category

#### **Product**
- **Use for**: Individual products
- **Fields**: Select a product from dropdown
- **Auto-generates**: Title and URL from product

#### **Page**
- **Use for**: CMS pages
- **Fields**: Select a page from dropdown
- **Auto-generates**: Title and URL from page

#### **Services**
- **Use for**: Services page link
- **Fields**: Optional custom title/URL
- **Default**: Links to `services.php`

#### **CEO Message**
- **Use for**: CEO Message page
- **Fields**: Optional custom title/URL
- **Default**: Links to `/ceo-message.php`

#### **Partners / Clients / Quality Certifications**
- **Use for**: Section anchors on homepage
- **Fields**: Optional custom title/URL
- **Default**: Links to homepage with anchor (e.g., `index.php#partners`)

### Step 4: Fill in Item Details
- **Icon**: Click "Choose Icon" to select a Font Awesome icon
- **Link Target**: Choose `_self` (same window) or `_blank` (new tab)
- **Mega Menu**: Enable if you want advanced dropdown (see Mega Menu section)

### Step 5: Save
- Click **"Add Item"** button
- The item will appear in your menu list

---

## 🔄 Organizing Menu Items

### Drag and Drop Reordering
1. **Hover** over any menu item
2. **Click and drag** the item to reorder
3. **Drop** it in the desired position
4. Click **"Save Menu Order"** button to save changes

### Creating Sub-Menus (Nested Items)
1. **Drag a menu item** and drop it **under another item**
2. It will become a child/sub-item
3. The parent item will show a dropdown arrow
4. Click **"Save Menu Order"** to save

### Editing Menu Items
1. Click the **"Edit"** button (pencil icon) on any menu item
2. Modify the item details
3. Click **"Update Item"** to save

### Deleting Menu Items
1. Click the **"Delete"** button (trash icon) on any menu item
2. Confirm the deletion
3. The item will be removed immediately

---

## 🎨 Using Mega Menu Features

### What is Mega Menu?
A mega menu is an advanced dropdown that can display:
- Multiple columns
- Custom content blocks (text, images, HTML)
- Featured products
- Categories with images
- Custom styling

### How to Enable Mega Menu

1. **Edit a Menu Item** that has children (sub-items)
2. Scroll down to **"Mega Menu Settings"**
3. **Toggle "Enable Mega Menu"** to ON
4. Configure settings:
   - **Layout**: Columns, Grid, or Full Width
   - **Width**: Auto, 600px, 800px, 1000px, 1200px, or Full
   - **Number of Columns**: 1-6 columns
   - **Background**: Color or image URL
   - **Custom CSS**: Add custom styles
5. Click **"Update Item"** to save
6. Click **"Manage Mega Menu Content"** to add widgets, products, or categories

### Managing Mega Menu Content

1. After enabling mega menu, click **"Manage Mega Menu Content"**
2. You'll see three tabs:

#### **Widgets Tab**
- Add custom content blocks
- Types: Text, Image, HTML, Button
- Organize by column and order
- Drag to reorder

#### **Products Tab**
- Add featured products to display
- Select products from dropdown
- Set display order

#### **Categories Tab**
- Add categories to display
- Toggle image/description display
- Set display order

---

## 📂 Selecting Categories for Products Menu

### Purpose
Control which categories appear in the Products menu dropdown on the frontend.

### Steps

1. **Go to Menu Categories**
   - Navigate to: Admin → **Menu Categories**
   - Or: Admin → Menus → **Menu Categories**

2. **Enable Category Selection**
   - Toggle **"Use Selected Categories"** to ON

3. **Select Categories**
   - Check the categories you want to show
   - Drag categories to reorder them
   - The order you set here will be the display order

4. **Save**
   - Click **"Save Selections"**
   - The Products menu will now only show your selected categories

### Toggle Off
- If you disable "Use Selected Categories", all active categories will be shown automatically

---

## 📍 Assigning Menus to Locations

### What are Menu Locations?
Menu locations determine where menus appear on your website (header, footer, etc.)

### Steps

1. **Go to Menu Locations**
   - Click **"Menu Locations"** button from the Menus page
   - Or go to: `admin/menu-locations.php`

2. **Select a Location**
   - Choose a location (e.g., "Header", "Footer")
   - Select a menu from the dropdown
   - Click **"Assign"** or **"Save"**

3. **Verify**
   - The menu will now appear in that location on the frontend

---

## 💡 Tips & Best Practices

### Menu Organization
- **Keep it simple**: Don't create too many nested levels (max 2-3 levels recommended)
- **Use icons**: Icons help users quickly identify menu items
- **Logical grouping**: Group related items together

### Mega Menu Best Practices
- **Use for main categories**: Enable mega menu for your most important menu items
- **Limit columns**: 3-4 columns work best for readability
- **Add products**: Showcase featured products in mega menus
- **Test on mobile**: Mega menus are responsive but test on different screen sizes

### Category Selection
- **Select 8-12 categories**: Too many can overwhelm users
- **Order by importance**: Put your most important categories first
- **Keep it updated**: Review and update selections periodically

### Performance
- **Limit menu items**: Too many items can slow down the menu
- **Optimize images**: If using images in mega menus, optimize them first

---

## 🔧 Troubleshooting

### Menu Not Appearing on Frontend?
1. Check if menu is assigned to a location (Menu Locations)
2. Verify menu items are active (`is_active = 1`)
3. Clear browser cache

### Mega Menu Not Showing?
1. Ensure mega menu is enabled for the menu item
2. Check if the menu item has children or content
3. Verify JavaScript is enabled in browser

### Categories Not Showing in Products Menu?
1. Check "Use Selected Categories" is enabled
2. Verify categories are selected in Menu Categories page
3. Ensure categories are active in Categories management

### Can't Drag Menu Items?
1. Make sure JavaScript is enabled
2. Try refreshing the page
3. Check browser console for errors

---

## 📞 Need Help?

If you encounter issues:
1. Check browser console for JavaScript errors
2. Verify database tables exist (run migrations)
3. Check PHP error logs
4. Ensure all required files are present

---

## 🎯 Quick Reference

| Action | Location |
|--------|----------|
| Create Menu | Admin → Menus → Add New Menu |
| Edit Menu | Admin → Menus → Click "Edit" |
| Add Menu Item | Menu Editor → Click "Add Item" |
| Enable Mega Menu | Edit Menu Item → Mega Menu Settings |
| Manage Mega Menu | Edit Menu Item → Manage Mega Menu Content |
| Select Categories | Admin → Menu Categories |
| Assign to Location | Admin → Menus → Menu Locations |

---

**Last Updated**: 2024
