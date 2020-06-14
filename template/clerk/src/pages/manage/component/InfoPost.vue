<template>
  <div>
    <van-cell-group>
      <van-field v-model="info.assistant_name" label="店员名称" placeholder="请输入店员名称"/>
      <CellSelector
        label="岗位"
        placeholder="请选择岗位"
        popupTitle="请选择岗位"
        :columns="jobs"
        @confirm="onJobs"
      />
      <van-field
        v-model="info.assistant_tel"
        label="手机号码"
        type="number"
        maxlength="11"
        placeholder="请输入手机号码"
      />
      <van-field
        v-model="info.password"
        label="登录密码"
        type="password"
        placeholder="请输入密码"
        v-if="!isEdit"
      />
      <van-cell class="cell-panel" title="是否启用" value-class="flex">
        <van-switch v-model="status" size="24px"/>
      </van-cell>
    </van-cell-group>
    <div class="fixed-foot-btn-group">
      <van-button size="normal" type="danger" round block :loading="isSaving" @click="onSave">保存</van-button>
    </div>
  </div>
</template>

<script>
const defaultData = {
  assistant_id: null,
  assistant_name: null,
  assistant_tel: null,
  password: null,
  jobs_id: null,
  status: 0
};
import { Switch } from "vant";
import CellSelector from "@/components/CellSelector";
import { validMobile, validUsername, validPassword } from "@/utils/validator";
export default {
  data() {
    return {
      status: this.info.status ? true : false
    };
  },
  props: {
    info: {
      type: Object,
      default: () => ({ ...defaultData })
    },
    jobs: Array,
    isSaving: Boolean
  },
  computed: {
    isEdit() {
      return this.info.assistant_id ? true : false;
    }
  },
  methods: {
    onJobs({ id, name }) {
      this.info.jobs_id = id;
    },
    vaild() {
      let info = this.info;
      if (
        !validUsername(info.assistant_name, "店员名称不能为空") ||
        !validUsername(info.jobs_id, "请选择岗位") ||
        !validMobile(info.assistant_tel) ||
        (!validPassword(info.password) && !this.isEdit)
      ) {
        return false;
      }
      info.status = this.status ? 1 : 0;
      return info;
    },
    onSave() {
      const info = this.vaild();
      if (info && !this.isSaving) {
        this.$emit("save", info);
      }
    }
  },
  components: {
    [Switch.name]: Switch,
    CellSelector
  }
};
</script>

<style scoped>
.flex {
  display: flex;
}
</style>
