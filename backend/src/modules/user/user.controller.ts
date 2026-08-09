import { Request, Response } from "express";
import * as userService from "./user.service";
import { AppError } from "../../utils/AppError";
import { PasswordResetRequestInput, PasswordResetInput, ChangePasswordInput, UpdateProfileInput } from "./user.interface";

export const register = async (req: Request, res: Response) => {
  const { name, phone, password } = req.body;
  // Public registration can only ever create "user" accounts.
  // Admin/manager accounts are created only by the seed script or an existing admin.
  const data = await userService.register({ name, phone, password, role: "user" });
  res.status(201).json({ success: true, data });
};

export const login = async (req: Request, res: Response) => {
  const { phone, password } = req.body;
  const data = await userService.login({ phone, password });
  res.json({ success: true, data });
};

export const devLogin = async (req: Request, res: Response) => {
  const role = req.body?.role;
  const data = await userService.devLogin(role);
  res.json({ success: true, data });
};

export const requestPasswordReset = async (req: Request, res: Response) => {
  const { phone } = req.body;
  await userService.requestPasswordReset({ phone });
  res.json({ success: true, message: "Password reset link sent to phone" });
};

export const resetPassword = async (req: Request, res: Response) => {
  const { token, newPassword } = req.body;
  await userService.resetPassword({ token, newPassword });
  res.json({ success: true, message: "Password reset successfully" });
};

export const changePassword = async (req: Request, res: Response) => {
  const userId = (req as any).user?.id;
  const { oldPassword, newPassword } = req.body;
  await userService.changePassword(userId, { oldPassword, newPassword });
  res.json({ success: true, message: "Password changed successfully" });
};

export const getProfile = async (req: Request, res: Response) => {
  const userId = (req as any).user?.id;
  const data = await userService.getProfile(userId);
  res.json({ success: true, data });
};

export const updateProfile = async (req: Request, res: Response) => {
  const userId = (req as any).user?.id;
  const { name, phone, shippingArea, shippingAddress } = req.body;
  const data = await userService.updateProfile(userId, {
    name,
    phone,
    shippingArea,
    shippingAddress,
  });
  res.json({ success: true, data });
};

export const getOrderHistory = async (req: Request, res: Response) => {
  const userId = (req as any).user?.id;
  const data = await userService.getOrderHistory(userId);
  res.json({ success: true, data });
};

export const getAddresses = async (req: Request, res: Response) => {
  const userId = (req as any).user?.id;
  const data = await userService.getAddresses(userId);
  res.json({ success: true, data });
};

export const createAddress = async (req: Request, res: Response) => {
  const userId = (req as any).user?.id;
  const data = await userService.createAddress(userId, req.body);
  res.status(201).json({ success: true, data });
};

export const updateAddress = async (req: Request, res: Response) => {
  const userId = (req as any).user?.id;
  const data = await userService.updateAddress(userId, Number(req.params.id), req.body);
  res.json({ success: true, data });
};

export const deleteAddress = async (req: Request, res: Response) => {
  const userId = (req as any).user?.id;
  const data = await userService.deleteAddress(userId, Number(req.params.id));
  res.json({ success: true, data });
};

export const getAll = async (req: Request, res: Response) => {
  const data = await userService.getAll();
  res.json({ success: true, data });
};

export const remove = async (req: Request, res: Response) => {
  await userService.remove(Number(req.params.id));
  res.json({ success: true, message: "User deleted" });
};
