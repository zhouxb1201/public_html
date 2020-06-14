<template>
  <div>
    <van-tabs v-model="active" class="tabs-group">
      <van-tab :title="item.text" v-for="(item,t) in tabs" :key="t">
        <component :is="'Export'+item.type+'Item'" :content="item.content" />
        <van-cell-group class="card-group-box">
          <van-cell :label="child.value" v-for="(child,n) in item.notes" :key="n">
            <span class="text-maintone" slot="title">{{child.title}}</span>
          </van-cell>
        </van-cell-group>
      </van-tab>
    </van-tabs>
  </div>
</template>

<script>
import ExportKeyItem from "./ExportKeyItem";
import ExportQrItem from "./ExportQrItem";
import { setSession, getSession, removeSession } from "@/utils/storage";
export default {
  data() {
    return {
      active: 0
    };
  },
  props: {
    type: String,
    keyType: String
  },
  computed: {
    tabs() {
      const arr = [
        {
          type: "Key",
          text: "",
          content: "",
          notes: [
            {
              title: "离线保存",
              value: "切勿保存至邮箱、记事本、网盘、聊天工具等，非常危险。"
            },
            {
              title: "请勿使用网络传输",
              value:
                "请勿通过网络工具传输，一旦被黑客获取将造成不可挽回的资产损失。建议离线设备通过扫二维码方式传输。"
            },
            {
              title: "密码管理工具保存",
              value: "建议使用密码管理工具保存管理。"
            }
          ]
        },
        {
          type: "Qr",
          text: "二维码",
          content: "",
          notes: [
            {
              title: "仅供直接扫描",
              value:
                "二维码禁止保存、截图、拍照。仅供用户在安全环境下直接扫描来方便导入钱包。"
            },
            {
              title: "在安全环境下使用",
              value:
                "请确保在四周无人及无摄像头的情况下使用。二维码一旦被他人获取将造成不可挽回的资产损失。"
            }
          ]
        }
      ];
      if (this.type == "eth") {
        if (this.keyType == "keystore") {
          arr[0].text = "KeyStore";
        }
        if (this.keyType == "privatekey") {
          arr[0].text = "私钥";
        }
      }
      if (this.type == "eos") {
        if (this.keyType == "keystore") {
          arr[0].text = "KeyStore";
        }
        if (this.keyType == "privatekey") {
          arr[0].text = "私钥";
        }
      }
      arr[0].content = getSession(this.type + this.keyType);
      arr[1].content = getSession(this.type + this.keyType);
      return arr;
    }
  },
  created() {
    if (!getSession(this.type + this.keyType)) {
      this.$Toast({
        duration: 3000,
        message: this.keyType + "已失效，请重新导出！"
      });
      this.$router.replace({
        name: "blockchain-centre",
        params: { type: this.type }
      });
    }
  },
  components: {
    ExportKeyItem,
    ExportQrItem
  }
};
</script>

<style scoped>
.cell-group {
  margin: 10px 0;
}
</style>

