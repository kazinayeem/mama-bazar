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
exports.removeMany = exports.remove = exports.create = exports.getById = exports.list = void 0;
const memoService = __importStar(require("./memo.service"));
const list = async (req, res) => {
    const result = await memoService.listMemos({
        page: Number(req.query.page),
        limit: Number(req.query.limit),
        search: req.query.search,
        entityType: req.query.entityType,
        folder: req.query.folder,
    });
    res.json({ success: true, ...result });
};
exports.list = list;
const getById = async (req, res) => {
    const data = await memoService.getMemo(Number(req.params.id));
    if (!data)
        return res.status(404).json({ success: false, message: "Memo not found" });
    res.json({ success: true, data });
};
exports.getById = getById;
const create = async (req, res) => {
    const data = await memoService.createMemo(req.body);
    res.status(201).json({ success: true, data });
};
exports.create = create;
const remove = async (req, res) => {
    await memoService.deleteMemo(Number(req.params.id));
    res.json({ success: true, message: "Memo deleted" });
};
exports.remove = remove;
const removeMany = async (req, res) => {
    const { ids } = req.body;
    const result = await memoService.deleteManyMemos(ids);
    res.json({ success: true, deleted: result.deleted });
};
exports.removeMany = removeMany;
//# sourceMappingURL=memo.controller.js.map