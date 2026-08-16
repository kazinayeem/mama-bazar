"use strict";
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __importStar = (this && this.__importStar) || (function () {
    var ownKeys = function(o) {
        ownKeys = Object.getOwnPropertyNames || function (o) {
            var ar = [];
            for (var k in o) if (Object.prototype.hasOwnProperty.call(o, k)) ar[ar.length] = k;
            return ar;
        };
        return ownKeys(o);
    };
    return function (mod) {
        if (mod && mod.__esModule) return mod;
        var result = {};
        if (mod != null) for (var k = ownKeys(mod), i = 0; i < k.length; i++) if (k[i] !== "default") __createBinding(result, mod, k[i]);
        __setModuleDefault(result, mod);
        return result;
    };
})();
Object.defineProperty(exports, "__esModule", { value: true });
exports.remove = exports.getAll = exports.deleteAddress = exports.updateAddress = exports.createAddress = exports.getAddresses = exports.getOrderHistory = exports.updateProfile = exports.getProfile = exports.changePassword = exports.resetPassword = exports.requestPasswordReset = exports.createAdmin = exports.devLogin = exports.login = exports.register = void 0;
const userService = __importStar(require("./user.service"));
const register = async (req, res) => {
    const { name, phone, password } = req.body;
    // Public registration can only ever create "user" accounts.
    // Admin/manager accounts are created only by the seed script or an existing admin.
    const data = await userService.register({ name, phone, password, role: "user" });
    res.status(201).json({ success: true, data });
};
exports.register = register;
const login = async (req, res) => {
    const { phone, password } = req.body;
    const data = await userService.login({ phone, password });
    res.json({ success: true, data });
};
exports.login = login;
const devLogin = async (req, res) => {
    const role = req.body?.role;
    const data = await userService.devLogin(role);
    res.json({ success: true, data });
};
exports.devLogin = devLogin;
const createAdmin = async (req, res) => {
    const { name, email, phone, password, role } = req.body;
    const data = await userService.createAdmin({ name, email, phone, password, role });
    res.status(201).json({ success: true, data, message: "Admin created successfully" });
};
exports.createAdmin = createAdmin;
const requestPasswordReset = async (req, res) => {
    const { phone } = req.body;
    await userService.requestPasswordReset({ phone });
    res.json({ success: true, message: "Password reset link sent to phone" });
};
exports.requestPasswordReset = requestPasswordReset;
const resetPassword = async (req, res) => {
    const { token, newPassword } = req.body;
    await userService.resetPassword({ token, newPassword });
    res.json({ success: true, message: "Password reset successfully" });
};
exports.resetPassword = resetPassword;
const changePassword = async (req, res) => {
    const userId = req.user?.id;
    const { oldPassword, newPassword } = req.body;
    await userService.changePassword(userId, { oldPassword, newPassword });
    res.json({ success: true, message: "Password changed successfully" });
};
exports.changePassword = changePassword;
const getProfile = async (req, res) => {
    const userId = req.user?.id;
    const data = await userService.getProfile(userId);
    res.json({ success: true, data });
};
exports.getProfile = getProfile;
const updateProfile = async (req, res) => {
    const userId = req.user?.id;
    const { name, phone, shippingArea, shippingAddress } = req.body;
    const data = await userService.updateProfile(userId, {
        name,
        phone,
        shippingArea,
        shippingAddress,
    });
    res.json({ success: true, data });
};
exports.updateProfile = updateProfile;
const getOrderHistory = async (req, res) => {
    const userId = req.user?.id;
    const data = await userService.getOrderHistory(userId);
    res.json({ success: true, data });
};
exports.getOrderHistory = getOrderHistory;
const getAddresses = async (req, res) => {
    const userId = req.user?.id;
    const data = await userService.getAddresses(userId);
    res.json({ success: true, data });
};
exports.getAddresses = getAddresses;
const createAddress = async (req, res) => {
    const userId = req.user?.id;
    const data = await userService.createAddress(userId, req.body);
    res.status(201).json({ success: true, data });
};
exports.createAddress = createAddress;
const updateAddress = async (req, res) => {
    const userId = req.user?.id;
    const data = await userService.updateAddress(userId, Number(req.params.id), req.body);
    res.json({ success: true, data });
};
exports.updateAddress = updateAddress;
const deleteAddress = async (req, res) => {
    const userId = req.user?.id;
    const data = await userService.deleteAddress(userId, Number(req.params.id));
    res.json({ success: true, data });
};
exports.deleteAddress = deleteAddress;
const getAll = async (req, res) => {
    const data = await userService.getAll();
    res.json({ success: true, data });
};
exports.getAll = getAll;
const remove = async (req, res) => {
    await userService.remove(Number(req.params.id));
    res.json({ success: true, message: "User deleted" });
};
exports.remove = remove;
//# sourceMappingURL=user.controller.js.map