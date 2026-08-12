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
exports.updateContactStatus = exports.getContactMessages = exports.submitContact = exports.remove = exports.update = exports.create = exports.getAll = exports.getBySlug = void 0;
const pagesService = __importStar(require("./pages.service"));
const AppError_1 = require("../../utils/AppError");
const getBySlug = async (req, res) => {
    const page = await pagesService.getPublishedBySlug(req.params.slug);
    if (!page)
        throw new AppError_1.AppError(404, "Page not found");
    res.json({ success: true, data: page });
};
exports.getBySlug = getBySlug;
const getAll = async (req, res) => {
    const pages = await pagesService.getAll();
    res.json({ success: true, data: pages });
};
exports.getAll = getAll;
const create = async (req, res) => {
    const userId = Number(req.user?.id);
    const { slug, title, content, status } = req.body;
    const data = await pagesService.create({ slug, title, content, status, updatedBy: userId });
    res.status(201).json({ success: true, data });
};
exports.create = create;
const update = async (req, res) => {
    const userId = Number(req.user?.id);
    const data = await pagesService.update(Number(req.params.id), { ...req.body, updatedBy: userId });
    res.json({ success: true, data });
};
exports.update = update;
const remove = async (req, res) => {
    const data = await pagesService.remove(Number(req.params.id));
    res.json({ success: true, data });
};
exports.remove = remove;
const submitContact = async (req, res) => {
    const data = await pagesService.createContactMessage(req.body);
    res.status(201).json({ success: true, data, message: "আপনার বার্তাটি পেয়েছি, শীঘ্রই যোগাযোগ করব।" });
};
exports.submitContact = submitContact;
const getContactMessages = async (req, res) => {
    const messages = await pagesService.getContactMessages();
    res.json({ success: true, data: messages });
};
exports.getContactMessages = getContactMessages;
const updateContactStatus = async (req, res) => {
    const data = await pagesService.setContactMessageStatus(Number(req.params.id), req.body.status);
    res.json({ success: true, data });
};
exports.updateContactStatus = updateContactStatus;
//# sourceMappingURL=pages.controller.js.map