<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255)->unique();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->unsignedInteger('category_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 50)->default('cash');
            $table->string('vendor', 255)->nullable();
            $table->unsignedInteger('member_id')->nullable();
            $table->string('member_name', 255)->nullable();
            $table->dateTime('expense_date');
            $table->string('reference_number', 100)->nullable();
            $table->string('attachment_url', 500)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->unsignedInteger('created_by_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('category_id')->references('id')->on('expense_categories')->onDelete('set null');
            $table->foreign('member_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');

            $table->index('expense_date', 'expenses_expense_date_idx');
            $table->index('member_id', 'expenses_member_id_idx');
            $table->index('category_id', 'expenses_category_id_idx');
            $table->index('status', 'expenses_status_idx');
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('customer_name', 255);
            $table->string('phone', 20);
            $table->string('email', 255)->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('booking_type', 100)->default('service');
            $table->string('service', 255)->nullable();
            $table->unsignedInteger('product_id')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->integer('quantity')->default(1);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('additional_cost', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'refunded'])->default('pending');
            $table->enum('status', ['pending', 'confirmed', 'active', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->string('attachment_url', 500)->nullable();
            $table->unsignedInteger('created_by_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('costs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 255);
            $table->string('cost_type', 100)->default('operational');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2);
            $table->unsignedInteger('supplier_id')->nullable();
            $table->unsignedInteger('product_id')->nullable();
            $table->unsignedInteger('order_id')->nullable();
            $table->unsignedInteger('booking_id')->nullable();
            $table->dateTime('cost_date');
            $table->string('payment_method', 50)->default('cash');
            $table->text('notes')->nullable();
            $table->string('attachment_url', 500)->nullable();
            $table->unsignedInteger('created_by_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('rentals', function (Blueprint $table) {
            $table->increments('id');
            $table->string('rental_item', 255);
            $table->unsignedInteger('product_id')->nullable();
            $table->string('customer_name', 255);
            $table->string('phone', 20);
            $table->string('email', 255)->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->integer('quantity')->default(1);
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->dateTime('return_date')->nullable();
            $table->enum('rate_type', ['daily', 'weekly', 'monthly'])->default('daily');
            $table->decimal('daily_rate', 12, 2)->default(0);
            $table->decimal('weekly_rate', 12, 2)->default(0);
            $table->decimal('monthly_rate', 12, 2)->default(0);
            $table->decimal('rate', 12, 2)->default(0);
            $table->integer('duration_units')->default(0);
            $table->decimal('security_deposit', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('additional_charge', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'refunded'])->default('pending');
            $table->enum('status', ['reserved', 'rented', 'returned', 'overdue', 'cancelled'])->default('reserved');
            $table->text('notes')->nullable();
            $table->string('attachment_url', 500)->nullable();
            $table->unsignedInteger('created_by_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('memos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 255)->nullable();
            $table->string('entity_type', 30);
            $table->integer('entity_id')->nullable();
            $table->string('url', 1000);
            $table->string('public_id', 500)->nullable();
            $table->string('filename', 500);
            $table->string('mime_type', 100);
            $table->integer('size')->default(0);
            $table->string('folder', 200)->default('memos');
            $table->text('notes')->nullable();
            $table->unsignedInteger('uploaded_by_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('uploaded_by_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memos');
        Schema::dropIfExists('rentals');
        Schema::dropIfExists('costs');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
